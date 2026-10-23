<?php

namespace ME\Utility\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ME\Utility\Services\MediaDownloader\GalleryDl;
use ME\Utility\Services\MediaDownloader\MediaDownloadService;
use ME\Utility\Services\MediaDownloader\YtDlp;

class MediaCheckCommand extends Command
{
    protected $signature = 'utility:media-check';

    protected $description = 'Check that the Media Downloader requirements (yt-dlp, FFmpeg, storage) are available';

    public function handle(YtDlp $ytDlp, MediaDownloadService $service, GalleryDl $galleryDl): int
    {
        $rows = [];
        $ok   = true;

        $check = function (string $name, bool $pass, string $detail) use (&$rows, &$ok) {
            $rows[] = [$name, $pass ? '<fg=green>OK</>' : '<fg=red>FAIL</>', $detail];
            $ok     = $ok && $pass;
        };

        $check('Laravel', true, app()->version());
        $check('PHP', version_compare(PHP_VERSION, '8.2.0', '>='), PHP_VERSION . ' (requires 8.2+)');

        $ytVersion = $ytDlp->version();
        $ageDays   = $ytVersion !== null ? $ytDlp->versionAgeInDays($ytVersion) : null;
        $outdated  = $ageDays !== null && $ageDays > YtDlp::OUTDATED_AFTER_DAYS;
        $check('yt-dlp', $ytVersion !== null && ! $outdated, match (true) {
            $ytVersion === null => "Not found: {$ytDlp->binary()} — see README (yt-dlp installation)",
            $outdated           => "{$ytVersion} is {$ageDays} days old — most sites will fail. Run: php artisan utility:media-version",
            default             => $ytVersion,
        });

        $ffVersion = $ytDlp->ffmpegVersion();
        $check('FFmpeg', $ffVersion !== null, $ffVersion ?? "Not found: {$ytDlp->ffmpegBinary()} — needed for merging and MP3");

        // Optional: only needed for images (TikTok photo posts, carousels…), never fails the check
        $gdVersion = $galleryDl->version();
        $rows[] = ['gallery-dl', $gdVersion !== null ? '<fg=green>OK</>' : '<fg=yellow>OPTIONAL</>',
            $gdVersion ?? "Not found: {$galleryDl->binary()} — image downloads are disabled (pipx install gallery-dl)"];

        $root = $service->temporaryRoot();
        try {
            MediaDownloadService::makeDirectory($root, 0775);
            $writable = is_writable($root);
        } catch (\Throwable) {
            $writable = false;
        }
        $free = $writable ? @disk_free_space($root) : false;
        $check('Storage', $writable, $writable
            ? $root . ($free !== false ? ' (' . round($free / 1024 ** 3, 1) . ' GB free)' : '')
            : "Not writable: {$root}");

        $this->line('<fg=gray>Checks run as "' . (function_exists('posix_geteuid') ? (posix_getpwuid(posix_geteuid())['name'] ?? '?') : get_current_user())
            . '". The web server / queue worker user (e.g. www-data) must also be able to run yt-dlp and write the storage path.</>');

        $check('Queue', true, config('utility.media_downloader.queue.enabled')
            ? 'enabled (' . (config('utility.media_downloader.queue.connection') ?? config('queue.default')) . ')'
            : 'disabled — downloads run after the HTTP response');

        $this->table(['Component', 'Status', 'Details'], $rows);

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
