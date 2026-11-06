<?php

namespace ME\Utility\Services\MediaDownloader;

/**
 * What the Media Downloader needs from the server, in plain words — shown on the page's
 * "Server setup" dialog so problems on shared hosting can be found without a terminal.
 */
class ServerCheck
{
    public const OK   = 'ok';
    public const WARN = 'warn';
    public const FAIL = 'fail';

    public function __construct(
        private readonly YtDlp $ytDlp,
        private readonly GalleryDl $galleryDl,
        private readonly ToolInstaller $installer,
        private readonly MediaDownloadService $service,
    ) {
    }

    /** @return list<array{key: string, name: string, status: string, detail: string}> */
    public function run(): array
    {
        $rows = [];
        $add  = function (string $key, string $name, string $status, string $detail) use (&$rows) {
            $rows[] = compact('key', 'name', 'status', 'detail');
        };

        $add('php', 'PHP', version_compare(PHP_VERSION, '8.2.0', '>=') ? self::OK : self::FAIL, PHP_VERSION . ' (8.2+ needed)');

        // Without proc_open nothing can run yt-dlp; shared hosts sometimes disable it
        $canRun = self::functionEnabled('proc_open');
        $add('proc_open', 'Run programs (proc_open)', $canRun ? self::OK : self::FAIL, $canRun
            ? 'enabled'
            : 'disabled by the host. cPanel → MultiPHP INI Editor / Select PHP Version → remove proc_open from disable_functions, or ask the host.');

        $installed = fn (string $tool) => $this->installer->isInstalled($tool) ? ' (installed by Server setup)' : '';

        $yt = $canRun ? $this->ytDlp->version() : null;
        $age = $yt !== null ? $this->ytDlp->versionAgeInDays($yt) : null;
        $add('yt-dlp', 'yt-dlp', match (true) {
            $yt === null                                => self::FAIL,
            $age !== null && $age > YtDlp::OUTDATED_AFTER_DAYS => self::WARN,
            default                                     => self::OK,
        }, match (true) {
            $yt === null => 'not found — click "Install / update tools"',
            $age !== null && $age > YtDlp::OUTDATED_AFTER_DAYS => "{$yt} is {$age} days old — click \"Install / update tools\"",
            default      => $yt . $installed(ToolInstaller::YT_DLP),
        });

        $ff = $canRun ? $this->ytDlp->ffmpegVersion() : null;
        $add('ffmpeg', 'FFmpeg', $ff !== null ? self::OK : self::FAIL, $ff !== null
            ? $ff . $installed(ToolInstaller::FFMPEG)
            : 'not found — click "Install / update tools" (needed to join video + audio and for MP3)');

        $gd = $canRun ? $this->galleryDl->version() : null;
        $add('gallery-dl', 'gallery-dl (optional)', $gd !== null ? self::OK : self::WARN, $gd
            ?? 'not installed — only photo posts (TikTok photos, carousels) are unavailable');

        $root = $this->service->temporaryRoot();
        try {
            MediaDownloadService::makeDirectory($root, 0775);
            $writable = is_writable($root);
        } catch (\Throwable) {
            $writable = false;
        }
        $free = $writable ? @disk_free_space($root) : false;
        $add('storage', 'Storage', $writable ? ($free !== false && $free < 2 * 1024 ** 3 ? self::WARN : self::OK) : self::FAIL, $writable
            ? ($free !== false ? round($free / 1024 ** 3, 1) . ' GB free' : 'writable') . ' — files wait here until you save them'
            : "not writable: {$root}");

        // Downloads run after the page has answered; that needs one of these (PHP-FPM / LiteSpeed)
        $background = function_exists('fastcgi_finish_request') || function_exists('litespeed_finish_request');
        $queue      = (bool) config('utility.media_downloader.queue.enabled');
        $add('background', 'Background downloads', $queue || $background ? self::OK : self::WARN, match (true) {
            $queue      => 'queue worker (' . (config('utility.media_downloader.queue.connection') ?? config('queue.default')) . ') — it must be running',
            $background => 'run after the response (no worker needed)',
            default     => 'this PHP mode keeps the page waiting until a download ends; prefer PHP-FPM or LiteSpeed',
        });

        $limit = (int) ini_get('max_execution_time');
        $add('time', 'Time limit', self::functionEnabled('set_time_limit') || $limit === 0 ? self::OK : self::WARN,
            self::functionEnabled('set_time_limit') || $limit === 0
                ? 'long downloads may run as long as needed'
                : "set_time_limit is disabled; downloads stop after {$limit} s");

        return $rows;
    }

    public static function functionEnabled(string $name): bool
    {
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return function_exists($name) && ! in_array($name, $disabled, true);
    }
}
