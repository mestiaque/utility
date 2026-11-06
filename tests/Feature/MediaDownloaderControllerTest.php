<?php

namespace ME\Utility\Tests\Feature;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Process;
use ME\Utility\Jobs\MediaDownloadJob;
use ME\Utility\Models\MediaDownload;
use ME\Utility\Tests\TestCase;

class MediaDownloaderControllerTest extends TestCase
{
    private const URL = 'https://www.youtube.com/watch?v=abc123';

    private function fakeMetadata(): void
    {
        Process::fake(['*--dump-single-json*' => Process::result(json_encode($this->fixture('youtube')))]);
    }

    public function test_page_requires_authentication(): void
    {
        $this->get(route('ut.media-downloader.index'))->assertRedirect();
    }

    public function test_page_renders(): void
    {
        $this->actingAs($this->user())
            ->get(route('ut.media-downloader.index'))
            ->assertOk()
            ->assertSee('Media Downloader')
            ->assertSee('Analyze')
            // Thumbnail is blurred behind a press-and-hold button
            ->assertSee('<div data-md="thumbBox" class="md-media rounded border d-none">', false)
            ->assertSee('class="md-peek"', false);
    }

    public function test_history_rows_have_redownload_and_open_link_buttons(): void
    {
        MediaDownload::create(['user_id' => 1, 'url' => self::URL, 'format' => 'video-720', 'status' => 'failed', 'title' => 'Old one']);

        $this->actingAs($this->user())
            ->get(route('ut.media-downloader.index'))
            ->assertOk()
            ->assertSee('onclick="mdRedownload(this)"', false)
            ->assertSee('data-format="video-720"', false)
            ->assertSee('href="' . e(self::URL) . '" target="_blank" rel="noopener noreferrer"', false)
            // Cancelling the confirm must not let the submit event reach the theme's page loader
            ->assertSee("if (!confirm('Remove this download from history?')) { event.stopPropagation(); return false; }", false);
    }

    public function test_analyze_requires_url(): void
    {
        $this->actingAs($this->user())
            ->postJson(route('ut.media-downloader.analyze'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('url');
    }

    public function test_analyze_rejects_local_and_bad_protocol_urls(): void
    {
        Process::fake();

        foreach (['http://localhost/video', 'http://127.0.0.1/x', 'file:///etc/passwd', 'http://192.168.0.1/'] as $url) {
            $this->actingAs($this->user())
                ->postJson(route('ut.media-downloader.analyze'), ['url' => $url])
                ->assertStatus(422)
                ->assertJsonValidationErrors('url');
        }

        Process::assertNothingRan();
    }

    public function test_url_without_scheme_gets_https(): void
    {
        $this->fakeMetadata();

        $this->actingAs($this->user())
            ->postJson(route('ut.media-downloader.analyze'), ['url' => 'www.youtube.com/watch?v=abc123'])
            ->assertOk()
            ->assertJsonPath('data.url', self::URL);

        // Other schemes are still rejected, not "fixed"
        foreach (['ftp://example.com/v.mp4', 'file:///etc/passwd', 'javascript:alert(1)', 'not a url'] as $url) {
            $this->actingAs($this->user())
                ->postJson(route('ut.media-downloader.analyze'), ['url' => $url])
                ->assertStatus(422);
        }
    }

    public function test_successful_analysis_returns_normalized_data(): void
    {
        $this->fakeMetadata();

        $response = $this->actingAs($this->user())
            ->postJson(route('ut.media-downloader.analyze'), ['url' => self::URL])
            ->assertOk()
            ->assertJsonPath('data.title', 'Example Video Title')
            ->assertJsonPath('data.platform', 'YouTube')
            ->assertJsonPath('data.duration', '05:42')
            ->assertJsonPath('data.formats.video.1.label', '1080p')
            ->assertJsonPath('data.formats.audio.2.key', 'audio-mp3');

        // Raw yt-dlp data / selectors never reach the browser
        $this->assertStringNotContainsString('selector', $response->getContent());
        $this->assertStringNotContainsString('storyboard', $response->getContent());

        // Arguments are passed as an array, with the URL after "--"
        Process::assertRan(fn ($process) => is_array($process->command)
            && array_slice($process->command, -2) === ['--', self::URL]
            && in_array('--ignore-config', $process->command, true));
    }

    public function test_failed_analysis_returns_friendly_message(): void
    {
        Process::fake(['*' => Process::result('', "ERROR: [youtube] abc: Private video. Sign in if you've been granted access\nTraceback /secret/path", 1)]);

        $response = $this->actingAs($this->user())
            ->postJson(route('ut.media-downloader.analyze'), ['url' => self::URL])
            ->assertStatus(400)
            ->assertJsonPath('reason', 'private_media');

        $this->assertStringContainsString('private', $response->json('message'));
        $this->assertStringNotContainsString('/secret/path', $response->getContent());
    }

    public function test_download_rejects_unknown_format(): void
    {
        $this->fakeMetadata();

        $this->actingAs($this->user())
            ->postJson(route('ut.media-downloader.download'), ['url' => self::URL, 'format' => 'video-9999'])
            ->assertStatus(422)
            ->assertJsonPath('reason', 'unsupported_format');

        $this->actingAs($this->user())
            ->postJson(route('ut.media-downloader.download'), ['url' => self::URL, 'format' => 'best; rm -rf /'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('format');

        $this->assertSame(0, MediaDownload::count());
    }

    public function test_download_creates_pending_record_and_dispatches_job(): void
    {
        Bus::fake();
        $this->fakeMetadata();

        $response = $this->actingAs($this->user())
            ->postJson(route('ut.media-downloader.download'), ['url' => self::URL, 'format' => 'video-720'])
            ->assertStatus(202)
            ->assertJsonPath('data.status', 'pending');

        $download = MediaDownload::firstOrFail();
        $this->assertSame('video-720', $download->format);
        $this->assertSame(1, (int) $download->user_id);
        $this->assertStringContainsString($download->uuid, $response->json('status_url'));

        Bus::assertDispatched(MediaDownloadJob::class, fn (MediaDownloadJob $job) => $job->downloadId === $download->id
            && $job->url === self::URL
            && $job->format === 'video-720'
            && $job->outputType === 'video');
    }

    public function test_status_is_only_visible_to_owner(): void
    {
        $download = MediaDownload::create(['user_id' => 1, 'url' => self::URL, 'format' => 'video-720']);

        $this->actingAs($this->user(1))
            ->getJson(route('ut.media-downloader.status', $download->uuid))
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.progress', null);

        $this->actingAs($this->user(2))
            ->getJson(route('ut.media-downloader.status', $download->uuid))
            ->assertNotFound();
    }

    public function test_cancel_endpoint(): void
    {
        $download = MediaDownload::create(['user_id' => 1, 'url' => self::URL, 'format' => 'video-720']);

        $this->actingAs($this->user())
            ->postJson(route('ut.media-downloader.cancel', $download->uuid))
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_destroy_removes_history_entry(): void
    {
        $download = MediaDownload::create(['user_id' => 1, 'url' => self::URL, 'format' => 'audio-mp3', 'status' => 'failed']);

        $this->actingAs($this->user())
            ->delete(route('ut.media-downloader.destroy', $download->uuid))
            ->assertRedirect();

        $this->assertModelMissing($download);
    }

    public function test_destroy_all_clears_only_own_history(): void
    {
        $mine  = MediaDownload::create(['user_id' => 1, 'url' => self::URL, 'format' => 'audio-mp3', 'status' => 'failed']);
        $mine2 = MediaDownload::create(['user_id' => 1, 'url' => self::URL, 'format' => 'video-720', 'status' => 'pending']);
        $other = MediaDownload::create(['user_id' => 2, 'url' => self::URL, 'format' => 'audio-mp3', 'status' => 'failed']);

        $this->actingAs($this->user())
            ->delete(route('ut.media-downloader.destroy-all'))
            ->assertRedirect();

        $this->assertModelMissing($mine);
        $this->assertModelMissing($mine2);
        $this->assertModelExists($other);
    }

    public function test_setup_lists_server_checks(): void
    {
        Process::fake(['*--version*' => Process::result('2099.01.01'), '*-version*' => Process::result('ffmpeg version 6.0')]);

        $checks = collect($this->actingAs($this->user())
            ->getJson(route('ut.media-downloader.setup'))
            ->assertOk()
            ->assertJsonStructure(['checks' => [['key', 'name', 'status', 'detail']], 'can_install'])
            ->json('checks'))->keyBy('key');

        $this->assertSame('ok', $checks['proc_open']['status']);
        $this->assertSame('ok', $checks['ffmpeg']['status']);
    }

    public function test_installed_tool_is_used_only_while_the_config_is_a_bare_name(): void
    {
        $dir       = sys_get_temp_dir() . '/md-tools-' . uniqid();
        $installer = new \ME\Utility\Services\MediaDownloader\ToolInstaller($dir);

        $this->assertSame('yt-dlp', $installer->resolve('yt-dlp', 'yt-dlp'));

        mkdir($dir . '/yt-dlp', 0755, true);
        touch($dir . '/yt-dlp/yt-dlp_linux');
        chmod($dir . '/yt-dlp/yt-dlp_linux', 0755);

        $this->assertSame($dir . '/yt-dlp/yt-dlp_linux', $installer->resolve('yt-dlp', 'yt-dlp'));
        $this->assertSame('/usr/local/bin/yt-dlp', $installer->resolve('yt-dlp', '/usr/local/bin/yt-dlp'));

        \Illuminate\Support\Facades\File::deleteDirectory($dir);
    }

    public function test_install_status_survives_requests_and_a_killed_run_goes_stale(): void
    {
        $dir       = sys_get_temp_dir() . '/md-tools-' . uniqid();
        $installer = new \ME\Utility\Services\MediaDownloader\ToolInstaller($dir);

        $installer->begin(['yt-dlp']);
        $this->assertTrue($installer->isBusy());
        $this->assertSame('queued', $installer->status()['yt-dlp']['state']);

        file_put_contents($dir . '/.install-status.json', json_encode(['yt-dlp' => ['state' => 'running', 'message' => 'x', 'at' => time() - 3600]]));
        $this->assertFalse($installer->isBusy());
        $this->assertSame('failed', $installer->status()['yt-dlp']['state']);

        \Illuminate\Support\Facades\File::deleteDirectory($dir);
    }
}
