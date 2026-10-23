<?php

namespace ME\Utility\Console\Commands;

use Illuminate\Console\Command;
use ME\Utility\Services\MediaDownloader\MediaDownloadService;

class MediaCleanupCommand extends Command
{
    protected $signature = 'utility:media-cleanup';

    protected $description = 'Remove expired Media Downloader temporary files and fail stuck downloads';

    public function handle(MediaDownloadService $service): int
    {
        $removed = $service->cleanup();

        $this->info("Removed {$removed} temporary download director" . ($removed === 1 ? 'y' : 'ies') . '.');

        return self::SUCCESS;
    }
}
