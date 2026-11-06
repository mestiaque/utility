<?php

namespace ME\Utility;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use ME\Utility\Console\Commands\MediaCheckCommand;
use ME\Utility\Console\Commands\MediaCleanupCommand;
use ME\Utility\Console\Commands\MediaInstallCommand;
use ME\Utility\Console\Commands\MediaVersionCommand;
use ME\Utility\Services\MediaDownloader\CookieJar;
use ME\Utility\Services\MediaDownloader\ErrorClassifier;
use ME\Utility\Services\MediaDownloader\FacebookFallback;
use ME\Utility\Services\MediaDownloader\FormatOptionBuilder;
use ME\Utility\Services\MediaDownloader\GalleryDl;
use ME\Utility\Services\MediaDownloader\MediaDownloadService;
use ME\Utility\Services\MediaDownloader\PlaylistBuilder;
use ME\Utility\Services\MediaDownloader\TelegramChannel;
use ME\Utility\Services\MediaDownloader\ToolInstaller;
use ME\Utility\Services\MediaDownloader\UrlValidator;
use ME\Utility\Services\MediaDownloader\YtDlp;

class UtilityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/routes/api.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'utility');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'utility');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->publishes([__DIR__ . '/Config' => config_path('utility')], 'utility-config');
        $this->publishes([__DIR__ . '/public' => public_path('/')], 'utility-assets');

        // Sidebar is a numeric array — array_merge ensures all packages append their items
        if (file_exists($sidebarPath = __DIR__ . '/Config/sidebar.php')) {
            Config::set('sidebar', array_merge(
                config('sidebar', []),
                require $sidebarPath
            ));
        }

        $this->registerMediaQueueConnection();

        if ($this->app->runningInConsole()) {
            $this->commands([
                MediaCheckCommand::class,
                MediaVersionCommand::class,
                MediaCleanupCommand::class,
                MediaInstallCommand::class,
            ]);

            if (config('utility.media_downloader.schedule_cleanup', true)) {
                $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
                    $schedule->command('utility:media-cleanup')->hourly()->withoutOverlapping();
                });
            }
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/config.php', 'utility');

        if (file_exists(__DIR__ . '/Config/permissions.php')) {
            $this->mergeConfigFrom(__DIR__ . '/Config/permissions.php', 'permissions');
        }

        $this->registerMediaDownloader();
    }

    /**
     * A queue connection just for media jobs: same driver as the app's "database" connection,
     * but with retry_after longer than the longest allowed download. The default of 90 s would
     * make Laravel re-run (and, with tries = 1, fail) any download that takes longer.
     */
    private function registerMediaQueueConnection(): void
    {
        if (config()->has('queue.connections.media-downloader') || ! is_array(config('queue.connections.database'))) {
            return;
        }

        $maxRun = (int) config('utility.media_downloader.timeout', 10800)
            + (int) config('utility.media_downloader.analyze_timeout', 60);

        Config::set('queue.connections.media-downloader', array_merge(config('queue.connections.database'), [
            'queue'       => config('utility.media_downloader.queue.name', 'media'),
            'retry_after' => $maxRun + 600,
        ]));
    }

    private function registerMediaDownloader(): void
    {
        $config = fn () => (array) config('utility.media_downloader', []);

        $this->app->singleton(UrlValidator::class, fn () => new UrlValidator(
            array_values(array_filter((array) ($config()['allowed_hosts'] ?? [])))
        ));

        $this->app->singleton(ToolInstaller::class, fn () => new ToolInstaller(
            (string) ($config()['tools_directory'] ?? storage_path('app/media-tools'))
        ));

        // A bare binary name falls back to the copy installed by "Server setup" (shared hosting)
        $this->app->singleton(YtDlp::class, fn ($app) => new YtDlp(
            binary: $app->make(ToolInstaller::class)->resolve(ToolInstaller::YT_DLP, (string) ($config()['yt_dlp_binary'] ?? 'yt-dlp')),
            ffmpegBinary: $app->make(ToolInstaller::class)->resolve(ToolInstaller::FFMPEG, (string) ($config()['ffmpeg_binary'] ?? 'ffmpeg')),
            analyzeTimeout: (int) ($config()['analyze_timeout'] ?? 60),
            downloadTimeout: (int) ($config()['timeout'] ?? 10800),
            maxFileSize: (int) ($config()['max_file_size'] ?? 2 * 1024 * 1024 * 1024),
            classifier: $app->make(ErrorClassifier::class),
            maxPlaylistItems: max(1, (int) ($config()['max_batch_items'] ?? 200)),
            stallTimeout: max(0, (int) ($config()['stall_timeout'] ?? 120)),
            concurrentFragments: max(1, (int) ($config()['concurrent_fragments'] ?? 4)),
            debugPagesDirectory: ($config()['debug_pages_directory'] ?? null) ?: null,
        ));

        $this->app->singleton(GalleryDl::class, fn ($app) => new GalleryDl(
            binary: (string) ($config()['gallery_dl_binary'] ?? 'gallery-dl'),
            timeout: (int) ($config()['timeout'] ?? 10800),
            maxItems: max(1, (int) ($config()['max_batch_items'] ?? 200)),
            classifier: $app->make(ErrorClassifier::class),
        ));

        $this->app->singleton(CookieJar::class, fn ($app) => new CookieJar(
            encrypter: $app->make('encrypter'),
            directory: (string) ($config()['cookies_directory'] ?? storage_path('app/private/media-downloader-cookies')),
        ));

        $this->app->singleton(TelegramChannel::class, fn () => new TelegramChannel(
            maxItems: max(1, (int) ($config()['max_batch_items'] ?? 200)),
            timeout: max(5, (int) ($config()['analyze_timeout'] ?? 60)),
        ));

        $this->app->singleton(MediaDownloadService::class, fn ($app) => new MediaDownloadService(
            validator: $app->make(UrlValidator::class),
            ytDlp: $app->make(YtDlp::class),
            builder: $app->make(FormatOptionBuilder::class),
            playlists: $app->make(PlaylistBuilder::class),
            galleryDl: $app->make(GalleryDl::class),
            cookies: $app->make(CookieJar::class),
            telegram: $app->make(TelegramChannel::class),
            facebook: new FacebookFallback($app->make(YtDlp::class)),
            cache: $app->make('cache.store'),
            config: $config(),
        ));
    }
}
