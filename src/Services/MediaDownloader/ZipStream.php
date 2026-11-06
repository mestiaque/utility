<?php

namespace ME\Utility\Services\MediaDownloader;

use Closure;
use RuntimeException;

/**
 * Writes a ZIP archive straight to the output while the files are read: nothing is staged in a
 * temporary ZIP, so packing a bulk download of large videos needs no extra disk space, the first
 * bytes go out right away and the exact size is known up front (Content-Length).
 *
 * Entries are stored, not compressed (media already is). Each entry's CRC-32 is known (or read
 * from the file) before its header is written, so no data descriptors are needed and every
 * unzip tool can read the result. ZIP64 records are added only when sizes, offsets or the
 * entry count need them.
 */
class ZipStream
{
    private const MAX16 = 0xFFFF;
    private const MAX32 = 0xFFFFFFFF;
    private const CHUNK = 1024 * 1024;

    /** @var list<array{name: string, size: int, crc: ?int, path: ?string, open: Closure}> */
    private array $entries = [];

    private int $dosTime;
    private int $dosDate;

    public function __construct()
    {
        $now = getdate();
        $this->dosTime = ($now['hours'] << 11) | ($now['minutes'] << 5) | intdiv($now['seconds'], 2);
        $this->dosDate = (max(0, $now['year'] - 1980) << 9) | ($now['mon'] << 5) | $now['mday'];
    }

    public function addFile(string $path, string $name): void
    {
        $this->entries[] = [
            'name' => $name,
            'size' => (int) filesize($path),
            'crc'  => null,
            'path' => $path,
            'open' => fn () => fopen($path, 'rb'),
        ];
    }

    /** @param Closure(): (resource|false) $open  stream of exactly $size bytes whose CRC-32 is $crc */
    public function addStream(string $name, int $size, int $crc, Closure $open): void
    {
        $this->entries[] = ['name' => $name, 'size' => $size, 'crc' => $crc, 'path' => null, 'open' => $open];
    }

    /** Exact number of bytes stream() will write. */
    public function size(): int
    {
        $offset = 0;
        $cdSize = 0;
        foreach ($this->entries as $entry) {
            $cdSize += strlen($this->centralHeader($entry, 0, $offset));
            $offset += strlen($this->localHeader($entry, 0)) + $entry['size'];
        }

        return $offset + $cdSize + strlen($this->end($offset, $cdSize));
    }

    /** @param Closure(string): void $write */
    public function stream(Closure $write): void
    {
        $offset  = 0;
        $central = '';

        foreach ($this->entries as $entry) {
            $crc = $entry['crc'] ?? (int) hexdec((string) hash_file('crc32b', $entry['path']));

            $header = $this->localHeader($entry, $crc);
            $write($header);
            $this->copy($entry, $write);

            $central .= $this->centralHeader($entry, $crc, $offset);
            $offset  += strlen($header) + $entry['size'];
        }

        $write($central);
        $write($this->end($offset, strlen($central)));
    }

    private function copy(array $entry, Closure $write): void
    {
        $in = ($entry['open'])();
        if (! is_resource($in)) {
            throw new RuntimeException("Cannot read {$entry['name']}");
        }

        $copied = 0;
        try {
            while ($copied < $entry['size'] && ! feof($in)) {
                $chunk = fread($in, min(self::CHUNK, $entry['size'] - $copied));
                if ($chunk === false || $chunk === '') {
                    break;
                }
                $write($chunk);
                $copied += strlen($chunk);
            }
        } finally {
            fclose($in);
        }

        // The headers already promised this size; a shorter file would corrupt the archive
        if ($copied !== $entry['size']) {
            throw new RuntimeException("{$entry['name']} changed while it was being zipped");
        }
    }

    private function localHeader(array $entry, int $crc): string
    {
        $zip64 = $entry['size'] >= self::MAX32;
        $size  = $zip64 ? self::MAX32 : $entry['size'];
        $extra = $zip64 ? pack('vvPP', 0x0001, 16, $entry['size'], $entry['size']) : '';

        return pack('VvvvvvVVVvv',
            0x04034b50, $zip64 ? 45 : 20, 0x0800, 0, $this->dosTime, $this->dosDate,
            $crc, $size, $size, strlen($entry['name']), strlen($extra)
        ) . $entry['name'] . $extra;
    }

    private function centralHeader(array $entry, int $crc, int $offset): string
    {
        $bigSize   = $entry['size'] >= self::MAX32;
        $bigOffset = $offset >= self::MAX32;

        $data  = ($bigSize ? pack('PP', $entry['size'], $entry['size']) : '') . ($bigOffset ? pack('P', $offset) : '');
        $extra = $data !== '' ? pack('vv', 0x0001, strlen($data)) . $data : '';
        $size  = $bigSize ? self::MAX32 : $entry['size'];

        return pack('VvvvvvvVVVvvvvvVV',
            0x02014b50, (3 << 8) | 45, $data !== '' ? 45 : 20, 0x0800, 0, $this->dosTime, $this->dosDate,
            $crc, $size, $size, strlen($entry['name']), strlen($extra), 0, 0, 0,
            0100644 << 16, $bigOffset ? self::MAX32 : $offset
        ) . $entry['name'] . $extra;
    }

    private function end(int $cdOffset, int $cdSize): string
    {
        $count = count($this->entries);
        $out   = '';

        if ($count >= self::MAX16 || $cdOffset >= self::MAX32 || $cdSize >= self::MAX32) {
            $out .= pack('VPvvVVPPPP', 0x06064b50, 44, 45, 45, 0, 0, $count, $count, $cdSize, $cdOffset);
            $out .= pack('VVPV', 0x07064b50, 0, $cdOffset + $cdSize, 1);
        }

        return $out . pack('VvvvvVVv',
            0x06054b50, 0, 0, min($count, self::MAX16), min($count, self::MAX16),
            min($cdSize, self::MAX32), min($cdOffset, self::MAX32), 0
        );
    }
}
