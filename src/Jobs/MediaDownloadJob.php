<?php

namespace ME\Utility\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use ME\Utility\Models\MediaDownload;
use ME\Utility\Services\MediaDownloader\Exceptions\MediaDownloaderException;
use ME\Utility\Services\MediaDownloader\MediaDownloadService;
use Throwable;

/**
 * Carries only identifiers — never media bytes. Runs on a queue worker when
 * utility.media_downloader.queue.enabled is true, otherwise after the HTTP response.
 */
class MediaDownloadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** A half-finished media download is not safely retryable; the user can start again. */
    public int $tries = 1;

    public int $timeout;

    public function __construct(
        public readonly int $downloadId,
        public readonly string $url,
        public readonly string $format,
        public readonly string $outputType,
    ) {
        // Leave room for analysis + post-processing on top of the yt-dlp timeout
        $this->timeout = (int) config('utility.media_downloader.timeout', 10800)
            + (int) config('utility.media_downloader.analyze_timeout', 60)
            + 60;
    }

    public function handle(MediaDownloadService $service): void
    {
        $download = MediaDownload::find($this->downloadId);

        if ($download === null) {
            return;
        }

        // Without a queue worker this runs inside PHP-FPM after the response; a bulk download
        // can take far longer than max_execution_time. yt-dlp has its own timeout.
        if (! app()->runningInConsole()) {
            @set_time_limit(0);
        }

        $service->run($download, $this->url, $this->format);
        $service->cleanupIfDue();
    }

    public function failed(?Throwable $e): void
    {
        MediaDownload::find($this->downloadId)?->markFailed(
            MediaDownloaderException::because(MediaDownloaderException::TIMEOUT)->userMessage()
        );
    }
}
