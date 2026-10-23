<?php

namespace ME\Utility\Tests;

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\File;
use ME\Utility\Services\MediaDownloader\UrlValidator;
use ME\Utility\Services\MediaDownloader\YtDlp;
use ME\Utility\Tests\Stubs\PassThroughMiddleware;
use ME\Utility\UtilityServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

require_once __DIR__ . '/Stubs/HostStubs.php';

abstract class TestCase extends Orchestra
{
    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Never touch real DNS in tests: every host resolves to a public documentation IP
        $this->app->instance(UrlValidator::class, new UrlValidator([], fn (string $host) => ['93.184.216.34']));

        $this->app->resolving(YtDlp::class, fn (YtDlp $ytDlp) => $ytDlp->pollInterval = 0);

        // Only this feature's table: some existing package migrations are MySQL-specific
        // and cannot run on the in-memory SQLite test database.
        (require __DIR__ . '/../src/database/migrations/2026_09_23_000001_create_ut_media_downloads_table.php')->up();
        (require __DIR__ . '/../src/database/migrations/2026_09_24_000001_add_batch_columns_to_ut_media_downloads_table.php')->up();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempDir);
        File::deleteDirectory($this->tempDir . '-cookies');

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [UtilityServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $this->tempDir = sys_get_temp_dir() . '/ut-media-tests-' . uniqid();

        $app['config']->set('database.default', 'testing');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('utility.media_downloader.temporary_directory', $this->tempDir);
        $app['config']->set('utility.media_downloader.min_free_space', 0);
        $app['config']->set('utility.media_downloader.bulk_delay', 0);
        $app['config']->set('utility.media_downloader.cookies_directory', $this->tempDir . '-cookies');

        $app['router']->aliasMiddleware('activityLog', PassThroughMiddleware::class);
        $app['view']->addNamespace('me', __DIR__ . '/Stubs/views');
    }

    protected function defineRoutes($router): void
    {
        // The host application normally provides this
        $router->get('/login', fn () => 'login')->name('login');
    }

    protected function user(int $id = 1): GenericUser
    {
        return new GenericUser(['id' => $id, 'name' => "User {$id}"]);
    }

    protected function cookiesTxt(): string
    {
        return "# Netscape HTTP Cookie File\n"
            . ".facebook.com\tTRUE\t/\tTRUE\t1893456000\tc_user\t100000000000001\n"
            . "#HttpOnly_.facebook.com\tTRUE\t/\tTRUE\t1893456000\txs\tsecret-session-value\n"
            . ".youtube.com\tTRUE\t/\tFALSE\t1893456000\tPREF\t\n";
    }

    protected function fixture(string $name): array
    {
        return json_decode(file_get_contents(__DIR__ . "/Fixtures/{$name}.json"), true);
    }
}
