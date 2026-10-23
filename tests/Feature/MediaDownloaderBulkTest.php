<?php

namespace ME\Utility\Tests\Feature;

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Process;
use ME\Utility\Jobs\MediaDownloadJob;
use ME\Utility\Models\MediaDownload;
use ME\Utility\Services\MediaDownloader\Data\MediaInfo;
use ME\Utility\Services\MediaDownloader\FormatOptionBuilder;
use ME\Utility\Services\MediaDownloader\MediaDownloadService;
use ME\Utility\Services\MediaDownloader\UrlValidator;
use ME\Utility\Tests\TestCase;

class MediaDownloaderBulkTest extends TestCase
{
    private const LIST_URL = 'https://www.youtube.com/@example/videos';

    protected function setUp(): void
    {
        parent::setUp();

        // Resolve "localhost" like the real resolver would, so the SSRF guard is exercised
        $this->app->instance(UrlValidator::class, new UrlValidator([], fn (string $host) => ['93.184.216.34']));
    }

    /** Channel listing for the list URL; the full youtube fixture for any single video. */
    private function fakeYtDlp(): void
    {
        $channel = json_encode($this->fixture('channel'));
        $video   = json_encode($this->fixture('youtube'));

        Process::fake(function (PendingProcess $process) use ($channel, $video) {
            $url = end($process->command);

            if (in_array('--dump-single-json', $process->command, true)) {
                return Process::result($url === self::LIST_URL ? $channel : $video);
            }

            $template = $process->command[array_search('-o', $process->command, true) + 1];
            file_put_contents(str_replace('%(ext)s', 'mp4', $template), "bytes of {$url}");

            return Process::result('');
        });
    }

    private function service(): MediaDownloadService
    {
        return $this->app->make(MediaDownloadService::class);
    }

    public function test_list_link_is_analyzed_as_playlist(): void
    {
        $this->fakeYtDlp();

        $response = $this->actingAs($this->user())
            ->postJson(route('ut.media-downloader.analyze'), ['url' => self::LIST_URL])
            ->assertOk()
            ->assertJsonPath('kind', 'playlist')
            ->assertJsonPath('data.title', 'Example Channel - Videos')
            ->assertJsonPath('data.count', 3)
            ->assertJsonPath('data.entries.0.title', 'First video')
            ->assertJsonPath('data.entries.0.duration', '02:05')
            ->assertJsonPath('data.entries.0.thumbnail', 'https://i.ytimg.com/vi/aaa/2.jpg')
            ->assertJsonPath('data.entries.0.url', null)           // video URLs stay server-side
            ->assertJsonPath('data.entries.1.duration', '1:02:05')
            ->assertJsonPath('data.entries.3.kind', 'playlist')
            ->assertJsonPath('data.entries.3.url', 'https://www.youtube.com/@example/shorts');

        $this->assertCount(4, $response->json('data.entries')); // entry without a URL skipped

        // One fast flat listing, never one call per video
        Process::assertRanTimes(fn ($p) => in_array('--flat-playlist', $p->command, true), 1);
    }

    public function test_start_batch_creates_one_download_per_selected_video(): void
    {
        Bus::fake();
        $this->fakeYtDlp();

        $response = $this->actingAs($this->user())
            ->postJson(route('ut.media-downloader.batch.start'), [
                'url'     => self::LIST_URL,
                'entries' => ['0', '1', '2', '3', '99'], // 2 = SSRF-blocked, 3 = nested list, 99 = unknown
                'format'  => '720',
            ])
            ->assertStatus(202);

        $batchId = $response->json('batch_id');
        $items   = MediaDownload::where('batch_id', $batchId)->orderBy('id')->get();

        $this->assertSame(['https://www.youtube.com/watch?v=aaa', 'https://www.youtube.com/watch?v=bbb'], $items->pluck('url')->all());
        $this->assertSame(['preset-720', 'preset-720'], $items->pluck('format')->all());
        $this->assertSame('Example Channel - Videos', $items->first()->batch_title);
        Bus::assertDispatchedTimes(MediaDownloadJob::class, 2);
    }

    public function test_start_batch_validation(): void
    {
        $this->fakeYtDlp();

        $this->actingAs($this->user())
            ->postJson(route('ut.media-downloader.batch.start'), ['url' => self::LIST_URL, 'entries' => ['0'], 'format' => 'video-720'])
            ->assertStatus(422)->assertJsonValidationErrors('format');

        $this->actingAs($this->user())
            ->postJson(route('ut.media-downloader.batch.start'), ['url' => self::LIST_URL, 'entries' => ['../x'], 'format' => '720'])
            ->assertStatus(422)->assertJsonValidationErrors('entries.0');

        // Only invalid entries → nothing queued
        $this->actingAs($this->user())
            ->postJson(route('ut.media-downloader.batch.start'), ['url' => self::LIST_URL, 'entries' => ['2', '3'], 'format' => '720'])
            ->assertStatus(422)->assertJsonPath('reason', 'unsupported_format');

        $this->assertSame(0, MediaDownload::count());
    }

    public function test_presets_pick_best_quality_at_or_below_target(): void
    {
        $info    = (new FormatOptionBuilder())->build('https://x.test/v', $this->fixture('youtube'));
        $info60  = (new FormatOptionBuilder())->build('https://x.test/v', $this->fixture('youtube-60fps'));
        $service = $this->service();

        $this->assertSame('video-1080', $service->resolvePreset($info, 'preset-2160')->key); // nothing higher exists
        $this->assertSame('video-720', $service->resolvePreset($info, 'preset-720')->key);
        $this->assertSame('video-360', $service->resolvePreset($info, 'preset-360')->key);
        $this->assertSame('video-best', $service->resolvePreset($info, 'preset-best')->key);
        $this->assertSame('audio-best', $service->resolvePreset($info, 'preset-audio')->key);
        $this->assertSame('audio-mp3', $service->resolvePreset($info, 'preset-mp3')->key);
        $this->assertSame('video-1080-60', $service->resolvePreset($info60, 'preset-1080')->key); // 60 fps preferred
        $this->assertSame('video-720', $service->resolvePreset($info60, 'preset-480')->key);     // smallest available
    }

    public function test_full_bulk_run_status_and_zip(): void
    {
        $this->fakeYtDlp();
        $batchId = $this->service()->startBatch(self::LIST_URL, ['0', '1'], '1080', 1);

        foreach (MediaDownload::where('batch_id', $batchId)->get() as $item) {
            $this->service()->run($item, $item->url, $item->format);
        }

        $status = $this->actingAs($this->user())
            ->getJson(route('ut.media-downloader.batch.status', $batchId))
            ->assertOk()
            ->assertJsonPath('data.done', true)
            ->assertJsonPath('data.counts.completed', 2)
            ->json('data');
        $this->assertNotNull($status['zip_url']);

        // Another user cannot see it
        $this->actingAs($this->user(2))->getJson(route('ut.media-downloader.batch.status', $batchId))->assertNotFound();

        $response = $this->actingAs($this->user())->get($status['zip_url']);
        $response->assertOk();
        $zipBytes = $response->streamedContent();

        $tmp = tempnam(sys_get_temp_dir(), 'zip');
        file_put_contents($tmp, $zipBytes);
        $zip = new \ZipArchive();
        $zip->open($tmp);
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $zip->close();
        unlink($tmp);

        sort($names);
        $this->assertSame(['first-video-1080p.mp4', 'second-video-1080p.mp4'], $names);

        // Files and the ZIP are removed after sending
        $this->assertSame(0, MediaDownload::where('batch_id', $batchId)->whereNotNull('file_path')->count());
        $this->assertSame([], glob($this->tempDir . '/batch-*.zip'));
    }

    public function test_site_block_skips_rest_of_batch_and_retry_requeues(): void
    {
        config(['utility.media_downloader.bulk_block_threshold' => 2]);
        Bus::fake();
        $channel = json_encode($this->fixture('channel'));

        $fixture = $this->fixture('channel');
        $fixture['entries'][2]['url'] = 'https://www.youtube.com/watch?v=ccc'; // make all three downloadable
        $channel = json_encode($fixture);

        Process::fake(function (PendingProcess $process) use ($channel) {
            if (end($process->command) === self::LIST_URL) {
                return Process::result($channel);
            }

            return Process::result('', 'ERROR: [TikTok] 1: Unable to download webpage: HTTP Error 403: Forbidden', 1);
        });

        $batchId = $this->service()->startBatch(self::LIST_URL, ['0', '1', '2'], '720', 1);
        [$a, $b, $c] = MediaDownload::where('batch_id', $batchId)->orderBy('id')->get()->all();

        $this->service()->run($a, $a->url, $a->format);
        $this->service()->run($b, $b->url, $b->format); // 2nd refusal in a row → the rest is skipped

        $this->assertStringContainsString('refused the request', $a->fresh()->error_message);
        $this->assertSame('failed', $c->fresh()->status);
        $this->assertStringStartsWith('Skipped', $c->fresh()->error_message);

        $status = $this->actingAs($this->user())
            ->getJson(route('ut.media-downloader.batch.status', $batchId))
            ->json('data');
        $this->assertNotNull($status['retry_url']);

        $this->actingAs($this->user())
            ->postJson($status['retry_url'])
            ->assertOk()
            ->assertJsonPath('data.counts.failed', 0)
            ->assertJsonPath('data.counts.active', 3);
        Bus::assertDispatchedTimes(MediaDownloadJob::class, 3 + 3);
    }

    public function test_cancel_batch_cancels_only_remaining_items(): void
    {
        Bus::fake();
        $this->fakeYtDlp();
        $batchId = $this->service()->startBatch(self::LIST_URL, ['0', '1'], 'mp3', 1);
        MediaDownload::where('batch_id', $batchId)->orderBy('id')->first()->markFailed('boom');

        $this->actingAs($this->user())
            ->postJson(route('ut.media-downloader.batch.cancel', $batchId))
            ->assertOk()
            ->assertJsonPath('data.counts.failed', 1)
            ->assertJsonPath('data.counts.cancelled', 1)
            ->assertJsonPath('data.done', true);
    }

    public function test_cleanup_keeps_batch_files_while_batch_runs_and_queued_items_alive(): void
    {
        $this->fakeYtDlp();
        Bus::fake();
        $batchId = $this->service()->startBatch(self::LIST_URL, ['0', '1'], '720', 1);
        [$first, $second] = MediaDownload::where('batch_id', $batchId)->orderBy('id')->get()->all();

        $this->service()->run($first, $first->url, $first->format);
        $first->forceFill(['completed_at' => now()->subHours(3)])->save();
        // Second item has been queued for 2 hours behind other work
        MediaDownload::whereKey($second->id)->update(['updated_at' => now()->subHours(2)]);

        $this->artisan('utility:media-cleanup')->assertSuccessful();

        $this->assertTrue($first->fresh()->hasFile(), 'file must survive while the batch is still running');
        $this->assertSame('pending', $second->fresh()->status, 'queued item must not be treated as crashed');
    }
}
