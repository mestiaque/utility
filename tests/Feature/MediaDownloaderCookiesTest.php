<?php

namespace ME\Utility\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use ME\Utility\Models\MediaDownload;
use ME\Utility\Services\MediaDownloader\CookieJar;
use ME\Utility\Services\MediaDownloader\MediaDownloadService;
use ME\Utility\Tests\TestCase;

class MediaDownloaderCookiesTest extends TestCase
{
    private const URL = 'https://www.facebook.com/share/r/abc/';

    private function jar(): CookieJar
    {
        return $this->app->make(CookieJar::class);
    }

    public function test_parses_netscape_cookie_domains(): void
    {
        $this->assertSame(['facebook.com', 'youtube.com'], CookieJar::parseDomains($this->cookiesTxt()));
        $this->assertSame([], CookieJar::parseDomains('just some text'));
        $this->assertSame([], CookieJar::parseDomains('{"json": true}'));
    }

    public function test_detects_whether_login_session_cookies_are_present(): void
    {
        $now = 1_800_000_000;

        // Fixture has c_user + xs for facebook, only PREF for youtube
        $this->assertSame(['facebook.com' => true, 'youtube.com' => false], \ME\Utility\Services\MediaDownloader\CookieJar::loginSessions($this->cookiesTxt(), $now));

        $loggedOut = "# Netscape HTTP Cookie File\n.facebook.com\tTRUE\t/\tTRUE\t1893456000\tdatr\tabc\n";
        $expired   = "# Netscape HTTP Cookie File\n.facebook.com\tTRUE\t/\tTRUE\t1700000000\tc_user\t1\n";
        $this->assertSame(['facebook.com' => false], \ME\Utility\Services\MediaDownloader\CookieJar::loginSessions($loggedOut, $now));
        $this->assertSame(['facebook.com' => false], \ME\Utility\Services\MediaDownloader\CookieJar::loginSessions($expired, $now));
    }

    public function test_facebook_parse_error_with_cookies_is_not_reported_as_login_required(): void
    {
        Process::fake(['*' => Process::result('', 'ERROR: [facebook] 224: Cannot parse data; please report this issue', 1)]);
        $url = 'https://www.facebook.com/reel/224';

        // Without cookies → ask for login
        $this->actingAs($this->user())->postJson(route('ut.media-downloader.analyze'), ['url' => $url])
            ->assertJsonPath('reason', 'login_required');

        // With cookies → login is not the problem
        $this->jar()->put(1, $this->cookiesTxt());
        $this->actingAs($this->user())->postJson(route('ut.media-downloader.analyze'), ['url' => $url])
            ->assertJsonPath('reason', 'site_changed');
    }

    public function test_cookies_are_stored_encrypted(): void
    {
        $this->jar()->put(1, $this->cookiesTxt());

        $stored = implode('', array_map('file_get_contents', File::files($this->tempDir . '-cookies')));
        $this->assertStringNotContainsString('secret-session-value', $stored);

        $summary = $this->jar()->summary(1);
        $this->assertSame(['facebook.com', 'youtube.com'], $summary['domains']);
        $this->assertFalse($this->jar()->has(2));
    }

    public function test_temporary_file_is_always_deleted(): void
    {
        $this->jar()->put(1, $this->cookiesTxt());
        $seen = null;

        try {
            $this->jar()->withTemporaryFile(1, $this->tempDir, function (?string $path) use (&$seen) {
                $seen = $path;
                $this->assertStringContainsString('secret-session-value', file_get_contents($path));
                $this->assertSame('0600', substr(sprintf('%o', fileperms($path)), -4));
                throw new \RuntimeException('yt-dlp crashed');
            });
        } catch (\RuntimeException) {
        }

        $this->assertNotNull($seen);
        $this->assertFileDoesNotExist($seen);
    }

    public function test_upload_and_delete_through_ui(): void
    {
        $this->actingAs($this->user())
            ->post(route('ut.media-downloader.cookies.store'), [
                'cookies' => UploadedFile::fake()->createWithContent('cookies.txt', $this->cookiesTxt()),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($this->jar()->has(1));

        $this->actingAs($this->user())
            ->get(route('ut.media-downloader.index'))
            ->assertSee('facebook.com')
            ->assertSee('facebook.com: logged in')
            ->assertDontSee('secret-session-value');

        $this->actingAs($this->user())->delete(route('ut.media-downloader.cookies.destroy'))->assertRedirect();
        $this->assertFalse($this->jar()->has(1));
    }

    public function test_invalid_cookie_file_is_rejected(): void
    {
        $this->actingAs($this->user())
            ->post(route('ut.media-downloader.cookies.store'), [
                'cookies' => UploadedFile::fake()->createWithContent('cookies.txt', 'not cookies'),
            ])
            ->assertSessionHasErrors('cookies');

        $this->assertFalse($this->jar()->has(1));
    }

    public function test_analysis_and_download_use_owner_cookies_only(): void
    {
        $this->jar()->put(1, $this->cookiesTxt());
        $metadata = json_encode($this->fixture('youtube'));
        $cookieFiles = [];

        Process::fake(function (PendingProcess $process) use ($metadata, &$cookieFiles) {
            $i = array_search('--cookies', $process->command, true);
            if ($i !== false) {
                $cookieFiles[] = $process->command[$i + 1];
                $this->assertFileExists($process->command[$i + 1]);
            }
            if (in_array('--dump-single-json', $process->command, true)) {
                return Process::result($metadata);
            }
            $template = $process->command[array_search('-o', $process->command, true) + 1];
            file_put_contents(str_replace('%(ext)s', 'mp4', $template), 'bytes');

            return Process::result('');
        });

        // Owner (user 1) → cookies passed, before "--" + URL
        $this->actingAs($this->user(1))
            ->postJson(route('ut.media-downloader.analyze'), ['url' => self::URL])
            ->assertOk();

        $download = MediaDownload::create(['user_id' => 1, 'url' => self::URL, 'format' => 'video-720', 'title' => 't']);
        $this->app->make(MediaDownloadService::class)->run($download, self::URL, 'video-720');
        $this->assertSame('completed', $download->fresh()->status);

        $this->assertCount(2, $cookieFiles);
        foreach ($cookieFiles as $file) {
            $this->assertFileDoesNotExist($file); // plain-text copy removed right after yt-dlp
        }
        Process::assertRan(fn ($p) => in_array('--cookies', $p->command, true)
            && array_slice($p->command, -2) === ['--', self::URL]);

        // Another user (no cookies) → separate cache entry, no cookies passed
        $cookieFiles = [];
        $this->actingAs($this->user(2))
            ->postJson(route('ut.media-downloader.analyze'), ['url' => self::URL])
            ->assertOk();
        $this->assertSame([], $cookieFiles);
    }
}
