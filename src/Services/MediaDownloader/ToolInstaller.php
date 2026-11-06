<?php

namespace ME\Utility\Services\MediaDownloader;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Installs yt-dlp and FFmpeg into the app's own storage, for hosts without root access
 * (cPanel / shared hosting): official release builds are downloaded over HTTPS, unpacked
 * and checked by running them. No terminal and no .env change is needed — a configured
 * binary that is only a bare name ("yt-dlp") falls back to the installed copy.
 *
 * Installing can take minutes (FFmpeg is about 150 MB), longer than a host lets a request
 * live, so the page starts it in the background and follows status().
 */
class ToolInstaller
{
    public const YT_DLP = 'yt-dlp';
    public const FFMPEG = 'ffmpeg';

    private const YT_DLP_RELEASE = 'https://github.com/yt-dlp/yt-dlp/releases/latest/download/';

    /** Tried in order: the builds made for yt-dlp, then the upstream they are based on. */
    private const FFMPEG_RELEASES = [
        'https://github.com/yt-dlp/FFmpeg-Builds/releases/download/latest/',
        'https://github.com/BtbN/FFmpeg-Builds/releases/download/latest/',
    ];

    private const MAGIC_ZIP = "PK\x03\x04";
    private const MAGIC_XZ  = "\xFD7zXZ\x00";

    /** A run that has said nothing for this long was killed by the host. */
    private const STALE_SECONDS = 900;

    public function __construct(private readonly string $directory)
    {
    }

    public function directory(): string
    {
        return rtrim($this->directory, '/');
    }

    /** Where the installed copy of a tool lives (whether or not it exists yet). */
    public function path(string $tool): string
    {
        return match ($tool) {
            // "onedir" build: an executable plus _internal/, nothing is unpacked to /tmp
            // (shared hosts often mount /tmp noexec, which breaks the single-file build)
            self::YT_DLP => $this->directory() . '/yt-dlp/yt-dlp_linux',
            self::FFMPEG => $this->directory() . '/ffmpeg/ffmpeg',
        };
    }

    public function isInstalled(string $tool): bool
    {
        return is_file($this->path($tool)) && is_executable($this->path($tool));
    }

    /**
     * The binary to run: an explicit path from the config always wins; a bare name ("yt-dlp")
     * uses the installed copy when there is one, otherwise $PATH.
     */
    public function resolve(string $tool, string $configured): string
    {
        if (str_contains($configured, DIRECTORY_SEPARATOR) || ! $this->isInstalled($tool)) {
            return $configured;
        }

        return $this->path($tool);
    }

    /** Null when the builds below exist for this server, else why not. */
    public function supported(): ?string
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            return 'Automatic install works on Linux servers only. Install yt-dlp and FFmpeg manually (see README).';
        }

        return $this->arch() === null ? 'Unsupported CPU (' . php_uname('m') . '). Install yt-dlp and FFmpeg manually.' : null;
    }

    // ── Background run ─────────────────────────────────────────────────────────

    /** Marks the tools as waiting, so status() is right before the background run starts. */
    public function begin(array $tools): void
    {
        File::ensureDirectoryExists($this->directory(), 0755);
        $this->writeStatus(array_fill_keys($tools, $this->entry('queued', 'Waiting…')));
    }

    /** Installs each tool; one failing never stops the next, and nothing here throws. */
    public function installAll(array $tools): void
    {
        foreach ($tools as $tool) {
            try {
                $this->report($tool, 'done', $this->install($tool));
            } catch (Throwable $e) {
                $this->report($tool, 'failed', $e->getMessage());
            }
        }
    }

    /** @return array<string, array{state: string, message: string, at: int}> */
    public function status(): array
    {
        $status = json_decode((string) @file_get_contents($this->statusFile()), true);
        if (! is_array($status)) {
            return [];
        }

        foreach ($status as $tool => $entry) {
            if ($this->isActive($entry) && time() - (int) $entry['at'] > self::STALE_SECONDS) {
                $status[$tool] = $this->entry('failed', 'The host stopped the install. Try again; if it repeats, use SSH: php artisan utility:media-install');
            }
        }

        return $status;
    }

    public function isBusy(): bool
    {
        return array_filter($this->status(), fn (array $entry) => $this->isActive($entry)) !== [];
    }

    // ── Install ────────────────────────────────────────────────────────────────

    /** Downloads (or updates) the tool and returns the version it reports. */
    public function install(string $tool): string
    {
        if ($reason = $this->supported()) {
            throw new RuntimeException($reason);
        }

        @set_time_limit(0);
        File::ensureDirectoryExists($this->directory(), 0755);

        $work = $this->directory() . '/.install-' . $tool . '-' . bin2hex(random_bytes(4));
        File::ensureDirectoryExists($work, 0755);

        try {
            $tool === self::YT_DLP ? $this->installYtDlp($work) : $this->installFfmpeg($work);
        } finally {
            File::deleteDirectory($work);
        }

        $this->report($tool, 'running', 'Checking that it runs…');
        [$version, $error] = $this->probe($tool);

        return $version ?? throw new RuntimeException("{$tool} was installed but does not run on this server: {$error}");
    }

    public function version(string $tool): ?string
    {
        return $this->probe($tool)[0];
    }

    /** @return array{0: ?string, 1: ?string} the version, or why there is none */
    private function probe(string $tool): array
    {
        if (! $this->isInstalled($tool)) {
            return [null, 'not installed'];
        }

        try {
            $result = Process::timeout(60)->run([$this->path($tool), $tool === self::YT_DLP ? '--version' : '-version']);
        } catch (Throwable $e) {
            return [null, $e->getMessage()];
        }

        $first = strtok(trim($result->output()), "\n");
        if ($result->successful() && $first !== false) {
            return [trim($first), null];
        }

        return [null, 'exit ' . $result->exitCode() . ' ' . mb_substr(trim($result->errorOutput()), 0, 300)];
    }

    private function installYtDlp(string $work): void
    {
        $unpacked = $work . '/yt-dlp';
        $this->report(self::YT_DLP, 'running', 'Downloading…');

        // Without PHP's zip extension the single-file build still works where /tmp allows it
        if (! class_exists(ZipArchive::class)) {
            File::ensureDirectoryExists($unpacked, 0755);
            $this->download(self::YT_DLP_RELEASE . $this->ytDlpAsset(), $unpacked . '/yt-dlp_linux', "\x7FELF");
        } else {
            $this->unzipYtDlp($work, $unpacked);
        }

        chmod($unpacked . '/yt-dlp_linux', 0755);
        $this->swap($unpacked, dirname($this->path(self::YT_DLP)));
    }

    private function unzipYtDlp(string $work, string $unpacked): void
    {
        $zipFile = $work . '/yt-dlp.zip';
        $this->download(self::YT_DLP_RELEASE . $this->ytDlpAsset() . '.zip', $zipFile, self::MAGIC_ZIP);

        $this->report(self::YT_DLP, 'running', 'Unpacking…');
        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            throw new RuntimeException('The downloaded yt-dlp archive is damaged. Please try again.');
        }
        $ok = $zip->extractTo($unpacked);
        $zip->close();

        // The archive has one executable next to _internal/; its name differs per CPU
        $exe = collect(File::files($unpacked))->first(fn ($f) => str_starts_with($f->getFilename(), 'yt-dlp'));
        if (! $ok || $exe === null) {
            throw new RuntimeException('The downloaded yt-dlp archive is incomplete. Please try again.');
        }
        rename($exe->getPathname(), $unpacked . '/yt-dlp_linux');
    }

    private function installFfmpeg(string $work): void
    {
        $archive = $work . '/ffmpeg.tar.xz';
        $asset   = 'ffmpeg-master-latest-' . ($this->arch() === 'arm64' ? 'linuxarm64' : 'linux64') . '-gpl.tar.xz';

        $this->report(self::FFMPEG, 'running', 'Downloading (about 150 MB)…');
        $errors = [];
        foreach (self::FFMPEG_RELEASES as $release) {
            try {
                $this->download($release . $asset, $archive, self::MAGIC_XZ);
                $errors = [];
                break;
            } catch (RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }
        if ($errors !== []) {
            throw new RuntimeException(implode(' ', array_unique($errors)));
        }

        // PHP cannot read .xz itself; tar + xz are present on practically every Linux host.
        // Only the two programs are unpacked: the archive holds far more than is needed.
        $this->report(self::FFMPEG, 'running', 'Unpacking…');
        $result = Process::timeout(600)->path($work)->run(['tar', '-xJf', $archive, '--wildcards', '*/bin/ffmpeg', '*/bin/ffprobe']);
        if (! $result->successful()) {
            throw new RuntimeException('Could not unpack FFmpeg (tar/xz failed): ' . trim($result->errorOutput()));
        }

        $source = dirname((string) (glob($work . '/*/bin/ffmpeg')[0] ?? ''));
        if (! is_file($source . '/ffmpeg')) {
            throw new RuntimeException('The downloaded FFmpeg archive is incomplete. Please try again.');
        }

        $unpacked = $work . '/ffmpeg-bin';
        File::ensureDirectoryExists($unpacked, 0755);
        foreach (['ffmpeg', 'ffprobe'] as $name) {
            if (is_file("{$source}/{$name}")) {
                rename("{$source}/{$name}", "{$unpacked}/{$name}");
                chmod("{$unpacked}/{$name}", 0755);
            }
        }

        $this->swap($unpacked, dirname($this->path(self::FFMPEG)));
    }

    /** $magic = the file's first bytes: a site can answer 200 with an HTML challenge page. */
    private function download(string $url, string $target, string $magic): void
    {
        try {
            $response = Http::timeout(900)->connectTimeout(20)->withOptions(['sink' => $target])->get($url);
        } catch (Throwable $e) {
            throw new RuntimeException('The server could not download ' . basename($url) . ': ' . $e->getMessage());
        }

        if (! $response->successful()) {
            throw new RuntimeException('The server could not download ' . basename($url) . ' (HTTP ' . $response->status() . ').');
        }

        if ((string) @file_get_contents($target, false, null, 0, strlen($magic)) !== $magic) {
            throw new RuntimeException('The download site did not send ' . basename($url) . ' (it sent something else, maybe a block page).');
        }
    }

    /** Replaces the installed copy only once the new one is complete. */
    private function swap(string $from, string $to): void
    {
        $old = $to . '.old';
        File::deleteDirectory($old);
        if (is_dir($to)) {
            rename($to, $old);
        }
        rename($from, $to);
        File::deleteDirectory($old);
    }

    private function ytDlpAsset(): string
    {
        return $this->arch() === 'arm64' ? 'yt-dlp_linux_aarch64' : 'yt-dlp_linux';
    }

    private function arch(): ?string
    {
        return match (strtolower(php_uname('m'))) {
            'x86_64', 'amd64'  => 'amd64',
            'aarch64', 'arm64' => 'arm64',
            default            => null,
        };
    }

    // ── Status file (a file, so it works whatever the cache driver is) ─────────

    private function statusFile(): string
    {
        return $this->directory() . '/.install-status.json';
    }

    private function entry(string $state, string $message): array
    {
        return ['state' => $state, 'message' => $message, 'at' => time()];
    }

    private function isActive(array $entry): bool
    {
        return in_array($entry['state'] ?? null, ['queued', 'running'], true);
    }

    private function report(string $tool, string $state, string $message): void
    {
        $this->writeStatus([$tool => $this->entry($state, $message)] + $this->status());
    }

    private function writeStatus(array $status): void
    {
        @file_put_contents($this->statusFile(), json_encode($status), LOCK_EX);
    }
}
