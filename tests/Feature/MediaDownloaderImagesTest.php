<?php

namespace ME\Utility\Tests\Feature;

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use ME\Utility\Models\MediaDownload;
use ME\Utility\Services\MediaDownloader\FormatOptionBuilder;
use ME\Utility\Services\MediaDownloader\GalleryDl;
use ME\Utility\Services\MediaDownloader\MediaDownloadService;
use ME\Utility\Tests\TestCase;

class MediaDownloaderImagesTest extends TestCase
{
    private const URL = 'https://www.tiktok.com/@photoperson/photo/7439';

    /**
     * yt-dlp does not support photo posts; gallery-dl lists / downloads the images.
     * Downloads write the requested --range positions into --directory.
     */
    private function fakeEngines(): void
    {
        $gallery = file_get_contents(__DIR__ . '/../Fixtures/gallery-tiktok-photo.json');

        Process::fake(function (PendingProcess $process) use ($gallery) {
            $cmd = $process->command;

            if (str_contains($cmd[0], 'yt-dlp')) {
                return Process::result('', 'ERROR: Unsupported URL: ' . self::URL, 1);
            }

            if (in_array('--version', $cmd, true)) {
                return Process::result("1.32.13\n");
            }

            if (in_array('--dump-json', $cmd, true)) {
                return Process::result($gallery);
            }

            // Download: --range "2" or "1-200" / "1,2,3"
            $range = $cmd[array_search('--range', $cmd, true) + 1];
            $dir   = $cmd[array_search('--directory', $cmd, true) + 1];
            $wanted = str_contains($range, '-') ? [1, 2, 3, 4] : array_map('intval', explode(',', $range));
            $names  = [1 => 'one.jpg', 2 => 'two.jpg', 3 => 'three.webp', 4 => 'song.mp3'];
            foreach ($wanted as $n) {
                file_put_contents($dir . '/' . sprintf('%04d', $n) . '-' . $names[$n], "bytes-{$n}");
            }

            return Process::result('');
        });
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('utility.media_downloader.yt_dlp_binary', '/opt/bin/yt-dlp');
        $app['config']->set('utility.media_downloader.gallery_dl_binary', '/opt/bin/gallery-dl');
    }

    private function service(): MediaDownloadService
    {
        return $this->app->make(MediaDownloadService::class);
    }

    public function test_gallery_dl_lists_only_images_with_their_positions(): void
    {
        $this->fakeEngines();
        $result = $this->app->make(GalleryDl::class)->images(self::URL);

        $this->assertSame([1, 2, 3], array_column($result['images'], 'position'));   // mp3 skipped
        $this->assertSame(['jpg', 'jpg', 'webp'], array_column($result['images'], 'extension'));
        $this->assertSame('Photo Person', $result['meta']['author']['nickname']);

        Process::assertRan(fn ($p) => str_contains($p->command[0], 'gallery-dl')
            && in_array('--config-ignore', $p->command, true)
            && array_slice($p->command, -2) === ['--', self::URL]);
    }

    public function test_photo_post_is_analyzed_as_images(): void
    {
        $this->fakeEngines();

        $response = $this->actingAs($this->user())
            ->postJson(route('ut.media-downloader.analyze'), ['url' => self::URL])
            ->assertOk()
            ->assertJsonPath('kind', 'media')
            ->assertJsonPath('data.platform', 'TikTok')
            ->assertJsonPath('data.title', 'Three photos from the trip')
            ->assertJsonPath('data.uploader', 'Photo Person')
            ->assertJsonPath('data.thumbnail', 'https://p16-sign.tiktokcdn.com/obj/one.jpeg')
            ->assertJsonPath('data.formats.video', [])
            ->assertJsonPath('data.formats.image.0.key', 'images-all')
            ->assertJsonPath('data.formats.image.0.label', 'All images (3)')
            ->assertJsonPath('data.formats.image.2.key', 'image-2')
            ->assertJsonPath('data.formats.image.2.resolution', '1080x1350')
            ->assertJsonPath('data.formats.image.2.preview', 'https://p16-sign.tiktokcdn.com/obj/two.jpeg');

        $this->assertCount(4, $response->json('data.formats.image'));
    }

    public function test_single_image_download(): void
    {
        $this->fakeEngines();
        $download = MediaDownload::create(['user_id' => 1, 'url' => self::URL, 'format' => 'image-2', 'title' => 'Three photos']);

        $this->service()->run($download, self::URL, 'image-2');
        $download->refresh();

        $this->assertSame('completed', $download->status, (string) $download->error_message);
        $this->assertSame('jpg', $download->extension);
        $this->assertSame('three-photos-image-2.jpg', $download->file_name);
        $this->assertSame('bytes-2', file_get_contents($download->file_path));
        Process::assertRan(fn ($p) => in_array('--directory', $p->command, true)
            && $p->command[array_search('--range', $p->command, true) + 1] === '2');
    }

    public function test_all_images_download_as_zip(): void
    {
        $this->fakeEngines();
        $download = MediaDownload::create(['user_id' => 1, 'url' => self::URL, 'format' => 'images-all', 'title' => 'Three photos']);

        $this->service()->run($download, self::URL, 'images-all');
        $download->refresh();

        $this->assertSame('completed', $download->status, (string) $download->error_message);
        $this->assertSame('zip', $download->extension);

        $zip = new \ZipArchive();
        $zip->open($download->file_path);
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $this->assertSame(['01.jpg', '02.jpg', '03.webp'], $names);   // only images, in post order
        $this->assertSame('bytes-3', $zip->getFromName('03.webp'));
        $zip->close();

        // Only the requested positions were downloaded
        Process::assertRan(fn ($p) => in_array('--directory', $p->command, true)
            && $p->command[array_search('--range', $p->command, true) + 1] === '1,2,3');
    }

    public function test_batch_zip_puts_all_images_in_one_folder_numbered_serially(): void
    {
        $this->fakeEngines();
        $batch = (string) \Illuminate\Support\Str::uuid();

        $photo = MediaDownload::create(['user_id' => 1, 'batch_id' => $batch, 'batch_title' => 'Profile', 'url' => self::URL, 'format' => 'images-all', 'title' => 'Beach day']);
        $this->service()->run($photo, self::URL, 'images-all');

        $photo2 = MediaDownload::create(['user_id' => 1, 'batch_id' => $batch, 'batch_title' => 'Profile', 'url' => self::URL, 'format' => 'images-all', 'title' => 'Beach day']);
        $this->service()->run($photo2, self::URL, 'images-all');

        // A finished video item of the same batch
        $video = MediaDownload::create(['user_id' => 1, 'batch_id' => $batch, 'batch_title' => 'Profile', 'url' => self::URL, 'format' => 'video-720', 'title' => 'Clip']);
        $dir = $this->service()->directoryFor($video);
        \ME\Utility\Services\MediaDownloader\MediaDownloadService::makeDirectory($this->tempDir, 0775);
        \ME\Utility\Services\MediaDownloader\MediaDownloadService::makeDirectory($dir, 0770);
        file_put_contents($dir . '/media.mp4', 'video-bytes');
        $video->markDownloading();
        $video->markCompleted($dir . '/media.mp4', 'clip-720p.mp4', 11, 'mp4');

        $response = $this->actingAs($this->user())->get(route('ut.media-downloader.batch.zip', $batch));
        $response->assertOk();

        $tmp = tempnam(sys_get_temp_dir(), 'zip');
        file_put_contents($tmp, $response->streamedContent());
        $zip = new \ZipArchive();
        $zip->open($tmp);
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        sort($names);
        $this->assertSame([
            'clip-720p.mp4',
            'images/001.jpg',
            'images/002.jpg',
            'images/003.webp',
            'images/004.jpg',
            'images/005.jpg',
            'images/006.webp',
        ], $names);
        $this->assertSame('bytes-2', $zip->getFromName('images/002.jpg'));   // first post: 001–003
        $this->assertSame('bytes-1', $zip->getFromName('images/004.jpg'));   // second post continues at 004
        $zip->close();
        unlink($tmp);

        $this->assertDirectoryDoesNotExist($this->tempDir . '/batch-' . $batch . '-files'); // temp unpack removed
    }

    public function test_incomplete_image_set_fails_with_the_real_reason(): void
    {
        $gallery = file_get_contents(__DIR__ . '/../Fixtures/gallery-tiktok-photo.json');

        Process::fake(function (PendingProcess $process) use ($gallery) {
            $cmd = $process->command;
            if (str_contains($cmd[0], 'yt-dlp')) {
                return Process::result('', 'ERROR: Unsupported URL', 1);
            }
            if (in_array('--version', $cmd, true)) {
                return Process::result("1.32.13\n");
            }
            if (in_array('--dump-json', $cmd, true)) {
                return Process::result($gallery);
            }
            // Only the first image arrives, then the site rate-limits
            $dir = $cmd[array_search('--directory', $cmd, true) + 1];
            file_put_contents($dir . '/20260101000000000001.jpg', 'bytes-1');

            return Process::result('', "[downloader.http][warning] '429 Too many requests'", 4);
        });

        $download = MediaDownload::create(['user_id' => 1, 'url' => self::URL, 'format' => 'images-all', 'title' => 'x']);
        $this->service()->run($download, self::URL, 'images-all');

        $this->assertSame('failed', $download->fresh()->status);
        $this->assertStringContainsString('refused the request', $download->fresh()->error_message);
    }

    public function test_bulk_preset_downloads_all_images_of_a_photo_post(): void
    {
        $gallery = json_decode(file_get_contents(__DIR__ . '/../Fixtures/gallery-tiktok-photo.json'), true);
        $images  = ['meta' => $gallery[0][1], 'images' => [
            ['position' => 1, 'url' => 'https://x.test/1.jpg', 'extension' => 'jpg', 'width' => null, 'height' => null],
            ['position' => 2, 'url' => 'https://x.test/2.jpg', 'extension' => 'jpg', 'width' => null, 'height' => null],
        ]];
        $info = (new FormatOptionBuilder())->withImages(null, self::URL, $images);

        $this->assertSame('images-all', $this->service()->resolvePreset($info, 'preset-1080')->key);
        $this->assertSame('images-all', $this->service()->resolvePreset($info, 'preset-best')->key);
    }

    public function test_without_gallery_dl_the_original_error_is_kept(): void
    {
        Process::fake(function (PendingProcess $process) {
            if (in_array('--version', $process->command, true)) {
                return Process::result('', 'not found', 127);   // gallery-dl not installed
            }

            return Process::result('', 'ERROR: Unsupported URL: ' . self::URL, 1);
        });

        $this->actingAs($this->user())
            ->postJson(route('ut.media-downloader.analyze'), ['url' => self::URL])
            ->assertStatus(422)
            ->assertJsonPath('reason', 'unsupported_url');
    }
}
