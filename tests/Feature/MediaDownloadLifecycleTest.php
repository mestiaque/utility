<?php

namespace ME\Utility\Tests\Feature;

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use ME\Utility\Models\MediaDownload;
use ME\Utility\Services\MediaDownloader\MediaDownloadService;
use ME\Utility\Tests\TestCase;

class MediaDownloadLifecycleTest extends TestCase
{
    private const URL = 'https://www.youtube.com/watch?v=abc123';

    private function pending(string $format = 'video-1080'): MediaDownload
    {
        return MediaDownload::create([
            'user_id' => 1,
            'url'     => self::URL,
            'format'  => $format,
            'title'   => 'Example Video Title',
        ]);
    }

    /** Fakes metadata + a download that writes $bytes into the output directory. */
    private function fakeYtDlp(?\Closure $onDownload = null, string $bytes = 'fake-media-bytes', int $exitCode = 0, string $stderr = ''): void
    {
        $metadata = json_encode($this->fixture('youtube'));

        Process::fake(function (PendingProcess $process) use ($metadata, $onDownload, $bytes, $exitCode, $stderr) {
            if (in_array('--dump-single-json', $process->command, true)) {
                return Process::result($metadata);
            }

            $template = $process->command[array_search('-o', $process->command, true) + 1];
            $onDownload?->__invoke();

            if ($exitCode === 0) {
                file_put_contents(str_replace('%(ext)s', 'mp4', $template), $bytes);
            }

            return Process::describe()
                ->output("[download] Destination: media.f137.mp4\n")
                ->output("ut-progress|50|100|NA|1024|3\n")
                ->errorOutput($stderr)
                ->exitCode($exitCode)
                ->iterations(2);
        });
    }

    private function service(): MediaDownloadService
    {
        return $this->app->make(MediaDownloadService::class);
    }

    public function test_media_queue_connection_outlives_the_longest_download(): void
    {
        $connection = config('queue.connections.media-downloader');

        $this->assertSame('database', $connection['driver']);
        $this->assertSame('media', $connection['queue']);
        $this->assertGreaterThan(
            (int) config('utility.media_downloader.timeout') + (int) config('utility.media_downloader.analyze_timeout'),
            $connection['retry_after']
        );
        $this->assertSame(90, config('queue.connections.database.retry_after') ?? 90); // app's own connection untouched
    }

    public function test_new_download_is_pending(): void
    {
        $download = $this->pending();

        $this->assertSame(MediaDownload::STATUS_PENDING, $download->fresh()->status);
        $this->assertTrue($download->isActive());
    }

    public function test_pending_to_downloading_to_completed(): void
    {
        $download = $this->pending();
        $statusWhileRunning = null;

        $this->fakeYtDlp(function () use ($download, &$statusWhileRunning) {
            $statusWhileRunning = $download->fresh()->status;
        });

        $this->service()->run($download, self::URL, 'video-1080');
        $download->refresh();

        $this->assertSame(MediaDownload::STATUS_DOWNLOADING, $statusWhileRunning);
        $this->assertSame(MediaDownload::STATUS_COMPLETED, $download->status);
        $this->assertSame(100.0, $download->progress);
        $this->assertSame('mp4', $download->extension);
        $this->assertSame(strlen('fake-media-bytes'), $download->filesize);
        $this->assertSame('example-video-title-1080p.mp4', $download->file_name);
        $this->assertTrue($download->hasFile());
        $this->assertNotNull($download->started_at);
        $this->assertNotNull($download->completed_at);

        // yt-dlp got the merge flags and an argv-safe URL
        Process::assertRan(fn ($p) => in_array('--merge-output-format', $p->command, true)
            && array_slice($p->command, -2) === ['--', self::URL]);
    }

    public function test_completed_file_is_streamed_then_removed(): void
    {
        $download = $this->pending();
        $this->fakeYtDlp();
        $this->service()->run($download, self::URL, 'video-1080');
        $dir = $this->service()->directoryFor($download);

        $response = $this->actingAs($this->user())->get(route('ut.media-downloader.file', $download->uuid));

        $response->assertOk();
        $this->assertSame('fake-media-bytes', $response->streamedContent());
        $this->assertStringContainsString('example-video-title-1080p.mp4', $response->headers->get('Content-Disposition'));
        $this->assertDirectoryDoesNotExist($dir);
        $this->assertNull($download->fresh()->file_path);

        // Second fetch: file is gone
        $this->actingAs($this->user())->get(route('ut.media-downloader.file', $download->uuid))->assertNotFound();
    }

    public function test_failure_marks_failed_with_friendly_message_and_cleans_up(): void
    {
        $download = $this->pending();
        $this->fakeYtDlp(exitCode: 1, stderr: "ERROR: [youtube] abc: Video unavailable. This video has been removed by the uploader\n/internal/trace");

        $this->service()->run($download, self::URL, 'video-1080');
        $download->refresh();

        $this->assertSame(MediaDownload::STATUS_FAILED, $download->status);
        $this->assertStringContainsString('removed', $download->error_message);
        $this->assertStringNotContainsString('/internal/trace', $download->error_message);
        $this->assertDirectoryDoesNotExist($this->service()->directoryFor($download));
    }

    public function test_unknown_format_fails(): void
    {
        $download = $this->pending('video-2160');
        $this->fakeYtDlp();

        $this->service()->run($download, self::URL, 'video-2160');

        $this->assertSame(MediaDownload::STATUS_FAILED, $download->fresh()->status);
    }

    public function test_cancelled_before_start_never_runs_yt_dlp_download(): void
    {
        $download = $this->pending();
        $this->service()->cancel($download);
        $this->fakeYtDlp();

        $this->service()->run($download, self::URL, 'video-1080');

        $this->assertSame(MediaDownload::STATUS_CANCELLED, $download->fresh()->status);
        Process::assertDidntRun(fn ($p) => in_array('-f', $p->command, true));
    }

    public function test_cancel_during_download_stops_process_and_cleans_up(): void
    {
        $download = $this->pending();

        // The user presses "Cancel" while yt-dlp is running
        $this->fakeYtDlp(fn () => MediaDownload::whereKey($download->id)->update(['status' => MediaDownload::STATUS_CANCELLED]));

        $this->service()->run($download, self::URL, 'video-1080');

        $this->assertSame(MediaDownload::STATUS_CANCELLED, $download->fresh()->status);
        $this->assertDirectoryDoesNotExist($this->service()->directoryFor($download));
    }

    public function test_finished_states_cannot_be_overwritten(): void
    {
        $download = $this->pending();
        $this->assertTrue($download->markCancelled());

        $this->assertFalse($download->markDownloading());
        $this->assertFalse($download->markCompleted('/tmp/x', 'x.mp4', 1, 'mp4'));
        $this->assertFalse($download->markFailed('late failure'));
        $this->assertFalse($download->updateProgress(50, null, null));
        $this->assertSame(MediaDownload::STATUS_CANCELLED, $download->fresh()->status);

        $failed = $this->pending();
        $failed->markFailed('boom');
        $this->assertFalse($failed->markCancelled());
        $this->assertSame(MediaDownload::STATUS_FAILED, $failed->fresh()->status);
    }

    public function test_cleanup_removes_expired_files_and_fails_stuck_downloads(): void
    {
        $download = $this->pending();
        $this->fakeYtDlp();
        $this->service()->run($download, self::URL, 'video-1080');
        $download->forceFill(['completed_at' => now()->subHours(2)])->save();

        $stuck = $this->pending();
        MediaDownload::whereKey($stuck->id)->update(['status' => 'downloading', 'updated_at' => now()->subDay()]);

        $this->artisan('utility:media-cleanup')->assertSuccessful();

        $this->assertDirectoryDoesNotExist($this->service()->directoryFor($download));
        $this->assertNull($download->fresh()->file_path);
        $this->assertSame(MediaDownload::STATUS_FAILED, $stuck->fresh()->status);
    }
}
