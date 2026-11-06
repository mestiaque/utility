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
 */
class ToolInstaller
{
    public const YT_DLP = 'yt-dlp';
    public const FFMPEG = 'ffmpeg';

    private const YT_DLP_RELEASE = 'https://github.com/yt-dlp/yt-dlp/releases/latest/download/';
    private const FFMPEG_RELEASE = 'https://johnvansickle.com/ffmpeg/releases/';

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

    /** Linux on x86_64 / arm64 only: the builds below exist for nothing else. */
    public function supported(): ?string
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            return 'Automatic install works on Linux servers only. Install yt-dlp and FFmpeg manually (see README).';
        }

        return $this->arch() === null ? 'Unsupported CPU (' . php_uname('m') . '). Install yt-dlp and FFmpeg manually.' : null;
    }

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

        return $this->version($tool) ?? throw new RuntimeException("{$tool} was installed but does not run on this server.");
    }

    public function version(string $tool): ?string
    {
        if (! $this->isInstalled($tool)) {
            return null;
        }

        try {
            $result = Process::timeout(30)->run([$this->path($tool), $tool === self::YT_DLP ? '--version' : '-version']);
        } catch (Throwable) {
            return null;
        }

        $first = strtok(trim($result->output()), "\n");

        return $result->successful() && $first !== false ? trim($first) : null;
    }

    private function installYtDlp(string $work): void
    {
        $asset = $this->arch() === 'arm64' ? 'yt-dlp_linux_aarch64.zip' : 'yt-dlp_linux.zip';
        $zipFile = $work . '/yt-dlp.zip';
        $this->download(self::YT_DLP_RELEASE . $asset, $zipFile);

        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            throw new RuntimeException('The downloaded yt-dlp archive is damaged. Please try again.');
        }
        $unpacked = $work . '/yt-dlp';
        $ok = $zip->extractTo($unpacked);
        $zip->close();

        // The archive has one executable next to _internal/; its name differs per CPU
        $exe = collect(File::files($unpacked))->first(fn ($f) => str_starts_with($f->getFilename(), 'yt-dlp'));
        if (! $ok || $exe === null) {
            throw new RuntimeException('The downloaded yt-dlp archive is incomplete. Please try again.');
        }
        rename($exe->getPathname(), $unpacked . '/yt-dlp_linux');
        chmod($unpacked . '/yt-dlp_linux', 0755);

        $this->swap($unpacked, dirname($this->path(self::YT_DLP)));
    }

    private function installFfmpeg(string $work): void
    {
        $archive = $work . '/ffmpeg.tar.xz';
        $this->download(self::FFMPEG_RELEASE . 'ffmpeg-release-' . $this->arch() . '-static.tar.xz', $archive);

        // PHP cannot read .xz itself; tar + xz are present on practically every Linux host
        $result = Process::timeout(300)->path($work)->run(['tar', '-xJf', $archive]);
        if (! $result->successful()) {
            throw new RuntimeException('Could not unpack FFmpeg (tar/xz failed): ' . trim($result->errorOutput()));
        }

        $source = collect(File::directories($work))->first(fn ($dir) => is_file($dir . '/ffmpeg'));
        if ($source === null) {
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

    private function download(string $url, string $target): void
    {
        try {
            $response = Http::timeout(600)->connectTimeout(20)->withOptions(['sink' => $target])->get($url);
        } catch (Throwable $e) {
            throw new RuntimeException('The server could not download ' . basename($url) . ': ' . $e->getMessage());
        }

        if (! $response->successful() || ! is_file($target) || filesize($target) === 0) {
            throw new RuntimeException('The server could not download ' . basename($url) . ' (HTTP ' . $response->status() . ').');
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

    private function arch(): ?string
    {
        return match (strtolower(php_uname('m'))) {
            'x86_64', 'amd64'  => 'amd64',
            'aarch64', 'arm64' => 'arm64',
            default            => null,
        };
    }
}
