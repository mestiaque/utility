<?php

namespace ME\Utility\Services\MediaDownloader;

use Closure;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Process;
use ME\Utility\Services\MediaDownloader\Data\MediaFormatOption;
use ME\Utility\Services\MediaDownloader\Exceptions\MediaDownloaderException;

/**
 * Thin, argument-array based wrapper around the yt-dlp binary.
 * Commands are always passed as arrays (never concatenated strings), and the URL always
 * follows "--" so it can never be parsed as an option.
 */
class YtDlp
{
    /** Marker yt-dlp prints on each progress line (see --progress-template). */
    public const PROGRESS_PREFIX = 'ut-progress|';

    private const PROGRESS_TEMPLATE = 'download:' . self::PROGRESS_PREFIX
        . '%(progress.downloaded_bytes)s|%(progress.total_bytes)s|%(progress.total_bytes_estimate)s'
        . '|%(progress.speed)s|%(progress.eta)s';

    /** yt-dlp releases often; site extractors older than this usually break. */
    public const OUTDATED_AFTER_DAYS = 120;

    /** Seconds between polls of a running download (0 in tests). */
    public float $pollInterval = 0.25;

    public function __construct(
        private readonly string $binary,
        private readonly string $ffmpegBinary,
        private readonly int $analyzeTimeout,
        private readonly int $downloadTimeout,
        private readonly int $maxFileSize,
        private readonly ErrorClassifier $classifier,
        private readonly int $maxPlaylistItems = 200,
        /** Stop a download only when it has produced no output for this long (0 = off) */
        private readonly int $stallTimeout = 120,
        /** Fragments fetched in parallel for HLS / DASH streams (1 = sequential) */
        private readonly int $concurrentFragments = 4,
        /** When set, analysis saves the pages yt-dlp fetched here (--write-pages), to debug a broken site */
        private readonly ?string $debugPagesDirectory = null,
    ) {
    }

    /** Fetch metadata only (no download). $cookiesFile = the user's own cookies.txt, if any. */
    public function metadata(string $url, ?string $cookiesFile = null): array
    {
        $command = [
            $this->binary,
            '--ignore-config',
            '--no-cache-dir',
            '--dump-single-json',
            // A single-video link (even with &list=) stays a single video; a playlist / channel /
            // profile link returns its entries without resolving each one (fast).
            '--no-playlist',
            '--flat-playlist',
            '--playlist-end', (string) $this->maxPlaylistItems,
            '--skip-download',
            '--no-warnings',
            ...self::cookieArgs($cookiesFile),
            ...($this->debugPagesDirectory ? ['--write-pages'] : []),
            '--',
            $url,
        ];

        $process = Process::timeout($this->analyzeTimeout);
        if ($this->debugPagesDirectory && is_dir($this->debugPagesDirectory) && is_writable($this->debugPagesDirectory)) {
            $process = $process->path($this->debugPagesDirectory); // --write-pages writes to the working directory
        }

        try {
            $result = $process->run($command);
        } catch (ProcessTimedOutException $e) {
            throw MediaDownloaderException::because(MediaDownloaderException::TIMEOUT, $e->getMessage(), $e);
        }

        if ($result->failed()) {
            throw $this->classifier->exception($result->errorOutput(), $result->exitCode());
        }

        $data = json_decode($result->output(), true);

        if (! is_array($data)) {
            throw MediaDownloaderException::because(MediaDownloaderException::EXTRACTION_FAILED, 'yt-dlp returned invalid JSON');
        }

        return $data;
    }

    /**
     * Fetches the pages yt-dlp would read for $url into $directory (--write-pages), whether or not
     * extraction then succeeds — for parsing a site yt-dlp's extractor can no longer read.
     *
     * @return string[] saved page files, largest first
     */
    public function writePages(string $url, ?string $cookiesFile, string $directory): array
    {
        $command = [
            $this->binary,
            '--ignore-config',
            '--no-cache-dir',
            '--no-playlist',
            '--skip-download',
            '--no-warnings',
            '--write-pages',
            ...self::cookieArgs($cookiesFile),
            '--',
            $url,
        ];

        try {
            Process::timeout($this->analyzeTimeout)->path($directory)->run($command);
        } catch (ProcessTimedOutException $e) {
            throw MediaDownloaderException::because(MediaDownloaderException::TIMEOUT, $e->getMessage(), $e);
        }

        $pages = glob(rtrim($directory, '/') . '/*.dump') ?: [];
        usort($pages, fn (string $a, string $b) => filesize($b) <=> filesize($a));

        return $pages;
    }

    /**
     * Download into $directory as "media.<ext>".
     *
     * @param Closure(?float $percent, ?int $speed, ?int $eta, ?string $stage): void $onProgress
     * @param Closure(): bool $shouldCancel polled while running; true stops the process
     */
    public function download(
        string $url,
        MediaFormatOption $option,
        string $directory,
        Closure $onProgress,
        Closure $shouldCancel,
        ?string $cookiesFile = null,
        ?string $infoJsonFile = null,
    ): void {
        $process = Process::timeout($this->downloadTimeout)
            ->path($directory)
            ->start($this->downloadCommand($url, $option, $directory, $cookiesFile, $infoJsonFile));

        $deadline     = microtime(true) + $this->downloadTimeout;
        $lastActivity = microtime(true);
        $parser       = new ProgressParser($onProgress);

        while ($process->running()) {
            $output = $process->latestOutput();
            if ($output !== '' || $process->latestErrorOutput() !== '') {
                $lastActivity = microtime(true);
            }
            $parser->feed($output);

            if ($shouldCancel()) {
                $this->terminate($process);
                throw MediaDownloaderException::because(MediaDownloaderException::CANCELLED);
            }

            if (microtime(true) > $deadline) {
                $this->terminate($process);
                throw MediaDownloaderException::because(MediaDownloaderException::TIMEOUT, "Download exceeded the maximum of {$this->downloadTimeout}s");
            }

            // A long download is fine as long as it moves; stop only when it has gone quiet.
            // Merging / converting prints nothing until done, so it is not treated as a stall.
            if ($this->stallTimeout > 0 && ! $parser->isPostProcessing() && microtime(true) - $lastActivity > $this->stallTimeout) {
                $this->terminate($process);
                throw MediaDownloaderException::because(MediaDownloaderException::TIMEOUT, "No download progress for {$this->stallTimeout}s");
            }

            if ($this->pollInterval > 0) {
                usleep((int) ($this->pollInterval * 1_000_000));
            }
        }

        try {
            $result = $process->wait();
        } catch (ProcessTimedOutException $e) {
            throw MediaDownloaderException::because(MediaDownloaderException::TIMEOUT, $e->getMessage(), $e);
        }

        $parser->feed($process->latestOutput());
        $parser->flush();

        if ($result->failed()) {
            throw $this->classifier->exception($result->errorOutput() . "\n" . $parser->tail(), $result->exitCode());
        }
    }

    /** @return string[] */
    /** @param string|null $infoJsonFile a prepared info dict to download from instead of extracting $url */
    public function downloadCommand(string $url, MediaFormatOption $option, string $directory, ?string $cookiesFile = null, ?string $infoJsonFile = null): array
    {
        $command = [
            $this->binary,
            '--ignore-config',
            '--no-cache-dir',
            '--no-playlist',
            '--newline',
            '--no-mtime',
            '--restrict-filenames',
            '--progress-template', self::PROGRESS_TEMPLATE,
            '--max-filesize', (string) $this->maxFileSize,
            '--concurrent-fragments', (string) max(1, $this->concurrentFragments),
            '-f', $option->selector,
            '-o', rtrim($directory, '/') . '/media.%(ext)s',
        ];

        // Only pass --ffmpeg-location for explicit paths; a bare name is found through $PATH
        if (str_contains($this->ffmpegBinary, DIRECTORY_SEPARATOR)) {
            array_push($command, '--ffmpeg-location', $this->ffmpegBinary);
        }

        if ($option->mergeOutputFormat !== null) {
            array_push($command, '--merge-output-format', $option->mergeOutputFormat);
        }

        if ($option->audioConvertTo !== null) {
            array_push($command, '-x', '--audio-format', $option->audioConvertTo, '--audio-quality', '0');
        }

        array_push($command, ...self::cookieArgs($cookiesFile));

        if ($infoJsonFile !== null) {
            array_push($command, '--load-info-json', $infoJsonFile);
        } else {
            array_push($command, '--', $url);
        }

        return $command;
    }

    /** @return string[] */
    private static function cookieArgs(?string $cookiesFile): array
    {
        return $cookiesFile !== null ? ['--cookies', $cookiesFile] : [];
    }

    public function version(): ?string
    {
        return $this->binaryVersion([$this->binary, '--version']);
    }

    /** yt-dlp versions are release dates (YYYY.MM.DD[.N]); null when unparsable. */
    public function versionAgeInDays(string $version): ?int
    {
        if (! preg_match('/^(\d{4})\.(\d{2})\.(\d{2})/', $version, $m) || ! checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }

        return (int) floor((time() - mktime(0, 0, 0, (int) $m[2], (int) $m[3], (int) $m[1])) / 86400);
    }

    public function ffmpegVersion(): ?string
    {
        $line = $this->binaryVersion([$this->ffmpegBinary, '-version']);

        return $line !== null && preg_match('/ffmpeg version (\S+)/i', $line, $m) ? $m[1] : $line;
    }

    public function maxPlaylistItems(): int
    {
        return $this->maxPlaylistItems;
    }

    public function binary(): string
    {
        return $this->binary;
    }

    public function ffmpegBinary(): string
    {
        return $this->ffmpegBinary;
    }

    /** SIGTERM, escalating to SIGKILL after a grace period (Symfony handles the escalation). */
    private function terminate(object $process): void
    {
        method_exists($process, 'stop') ? $process->stop(5) : $process->signal(15);
    }

    private function binaryVersion(array $command): ?string
    {
        try {
            $result = Process::timeout(15)->run($command);
        } catch (\Throwable) {
            return null;
        }

        if ($result->failed()) {
            return null;
        }

        $first = strtok(trim($result->output()), "\n");

        return $first === false ? null : trim($first);
    }
}
