<?php

namespace ME\Utility\Tests\Unit;

use Illuminate\Support\Facades\Process;
use ME\Utility\Services\MediaDownloader\Data\MediaFormatOption;
use ME\Utility\Services\MediaDownloader\ErrorClassifier;
use ME\Utility\Services\MediaDownloader\Exceptions\MediaDownloaderException;
use ME\Utility\Services\MediaDownloader\YtDlp;
use ME\Utility\Tests\TestCase;

class YtDlpStallTest extends TestCase
{
    private function ytDlp(int $stall): YtDlp
    {
        $ytDlp = new YtDlp('yt-dlp', 'ffmpeg', 60, 10800, 1_000_000, new ErrorClassifier(), 200, $stall, 4);
        $ytDlp->pollInterval = 0.2;

        return $ytDlp;
    }

    private function option(): MediaFormatOption
    {
        return new MediaFormatOption('video-720', MediaFormatOption::TYPE_VIDEO, '720p', 'mp4', '136+140');
    }

    public function test_a_download_that_goes_quiet_is_stopped(): void
    {
        // Runs for 10 polls (~2s) without printing anything
        Process::fake(['*' => Process::describe()->iterations(10)]);

        try {
            $this->ytDlp(1)->download('https://x.test/v', $this->option(), sys_get_temp_dir(), fn () => null, fn () => false);
            $this->fail('Expected a stall timeout');
        } catch (MediaDownloaderException $e) {
            $this->assertSame(MediaDownloaderException::TIMEOUT, $e->reason);
            $this->assertStringContainsString('No download progress for 1s', $e->technical);
        }
    }

    public function test_a_long_download_that_keeps_progressing_is_not_stopped(): void
    {
        // ~2s long, but prints a progress line on every poll
        $process = Process::describe();
        for ($i = 1; $i <= 10; $i++) {
            $process->output("ut-progress|{$i}|10|NA|100|1\n");
        }
        Process::fake(['*' => $process->iterations(10)]);

        $this->ytDlp(1)->download('https://x.test/v', $this->option(), sys_get_temp_dir(), fn () => null, fn () => false);

        Process::assertRan(fn ($p) => in_array('--concurrent-fragments', $p->command, true));
        $this->addToAssertionCount(1); // no exception = not treated as stuck
    }
}
