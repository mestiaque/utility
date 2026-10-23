<?php

namespace ME\Utility\Services\MediaDownloader;

use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Process;
use ME\Utility\Services\MediaDownloader\Exceptions\MediaDownloaderException;

/**
 * Image engine: gallery-dl finds and downloads the images of a post (TikTok photo posts,
 * Instagram / X carousels, Pinterest, Reddit, Imgur …), which yt-dlp does not handle.
 * Same safety rules as YtDlp: argument arrays only, URL after "--", user config ignored.
 */
class GalleryDl
{
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'heic', 'bmp'];

    private ?bool $available = null;

    public function __construct(
        private readonly string $binary,
        private readonly int $timeout,
        private readonly int $maxItems,
        private readonly ErrorClassifier $classifier,
    ) {
    }

    /** gallery-dl is optional: without it links simply have no image options. */
    public function available(): bool
    {
        return $this->available ??= $this->version() !== null;
    }

    public function version(): ?string
    {
        try {
            $result = Process::timeout(15)->run([$this->binary, '--version']);
        } catch (\Throwable) {
            return null;
        }

        return $result->successful() ? (trim(strtok($result->output(), "\n") ?: '') ?: null) : null;
    }

    public function binary(): string
    {
        return $this->binary;
    }

    /**
     * Lists the images of a post without downloading them.
     *
     * "position" is the file's place among everything gallery-dl found (images and videos),
     * which is what --range selects when downloading.
     *
     * @return array{meta: array, images: array<int, array{position: int, url: string, extension: string, width: ?int, height: ?int}>}
     */
    public function images(string $url, ?string $cookiesFile = null): array
    {
        $command = [
            $this->binary,
            '--config-ignore',
            '--dump-json',
            '--range', '1-' . $this->maxItems,
            ...self::cookieArgs($cookiesFile),
            '--',
            $url,
        ];

        try {
            $result = Process::timeout($this->timeout)->run($command);
        } catch (ProcessTimedOutException $e) {
            throw MediaDownloaderException::because(MediaDownloaderException::TIMEOUT, $e->getMessage(), $e);
        }

        $messages = json_decode($result->output(), true);

        if (! is_array($messages)) {
            if ($result->failed()) {
                throw $this->classifier->exception($result->errorOutput(), $result->exitCode());
            }

            return ['meta' => [], 'images' => []];
        }

        $meta     = [];
        $images   = [];
        $position = 0;

        foreach ($messages as $message) {
            if (! is_array($message) || ! isset($message[0])) {
                continue;
            }

            // [2, {directory metadata}] – post info (title, author, …)
            if ($message[0] === 2 && is_array($message[1] ?? null) && $meta === []) {
                $meta = $message[1];
                continue;
            }

            // [3, "file url", {file metadata}] – one file of the post
            if ($message[0] !== 3 || ! is_string($message[1] ?? null)) {
                continue;
            }

            $position++;
            $file = is_array($message[2] ?? null) ? $message[2] : [];
            $ext  = strtolower((string) ($file['extension'] ?? pathinfo(parse_url($message[1], PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)));

            if (! in_array($ext, self::IMAGE_EXTENSIONS, true) || ! preg_match('#^https?://#i', $message[1])) {
                continue; // videos are yt-dlp's job; "ytdl:" / text entries are not images
            }

            $meta = $meta ?: $file;
            $images[] = [
                'position'  => $position,
                'url'       => $message[1],
                'extension' => $ext === 'jpeg' ? 'jpg' : $ext,
                'width'     => isset($file['width']) && is_numeric($file['width']) ? (int) $file['width'] : null,
                'height'    => isset($file['height']) && is_numeric($file['height']) ? (int) $file['height'] : null,
            ];
        }

        return ['meta' => $meta, 'images' => $images];
    }

    /**
     * Downloads the chosen image positions (or all) into $directory.
     *
     * @param int[]|null $positions null = every file of the post
     * @return string[] downloaded image paths, in post order
     */
    public function download(string $url, string $directory, ?array $positions, ?string $cookiesFile = null): array
    {
        $command = [
            $this->binary,
            '--config-ignore',
            '--no-mtime',
            '--range', $positions === null ? '1-' . $this->maxItems : implode(',', array_map('intval', $positions)),
            '--directory', $directory,
            // Unique, safe names (several files of a post can share "num" / original name);
            // files are fetched one after another, so the timestamp also keeps post order
            '--filename', '{_now:%Y%m%d%H%M%S%f}.{extension}',
            ...self::cookieArgs($cookiesFile),
            '--',
            $url,
        ];

        try {
            $result = Process::timeout($this->timeout)->path($directory)->run($command);
        } catch (ProcessTimedOutException $e) {
            throw MediaDownloaderException::because(MediaDownloaderException::TIMEOUT, $e->getMessage(), $e);
        }

        $files = array_values(array_filter(
            glob(rtrim($directory, '/') . '/*') ?: [],
            fn (string $f) => is_file($f) && in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), self::IMAGE_EXTENSIONS, true)
        ));
        sort($files, SORT_NATURAL);

        if ($files === []) {
            throw $result->failed()
                ? $this->classifier->exception($result->errorOutput(), $result->exitCode())
                : MediaDownloaderException::because(MediaDownloaderException::EXTRACTION_FAILED, 'gallery-dl finished without image files');
        }

        // Some requested images failed (e.g. HTTP 429 half-way): report it instead of
        // silently handing over an incomplete set
        if ($result->failed() && $positions !== null && count($files) < count($positions)) {
            throw $this->classifier->exception(
                $result->errorOutput() . "\nDownloaded " . count($files) . ' of ' . count($positions) . ' images',
                $result->exitCode(),
            );
        }

        return $files;
    }

    /** @return string[] */
    private static function cookieArgs(?string $cookiesFile): array
    {
        return $cookiesFile !== null ? ['--cookies', $cookiesFile] : [];
    }
}
