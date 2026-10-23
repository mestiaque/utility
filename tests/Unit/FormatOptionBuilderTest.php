<?php

namespace ME\Utility\Tests\Unit;

use ME\Utility\Services\MediaDownloader\Data\MediaInfo;
use ME\Utility\Services\MediaDownloader\FormatOptionBuilder;
use PHPUnit\Framework\TestCase;

class FormatOptionBuilderTest extends TestCase
{
    private function build(string $fixture, string $url = 'https://www.youtube.com/watch?v=abc123'): MediaInfo
    {
        $raw = json_decode(file_get_contents(__DIR__ . "/../Fixtures/{$fixture}.json"), true);

        return (new FormatOptionBuilder())->build($url, $raw);
    }

    public function test_normalizes_metadata(): void
    {
        $info = $this->build('youtube');

        $this->assertSame('Example Video Title', $info->title);
        $this->assertSame('YouTube', $info->platform);
        $this->assertSame('Example Channel', $info->uploader);
        $this->assertSame('05:42', $info->durationFormatted());
        $this->assertSame('https://i.ytimg.com/vi/abc123/maxresdefault.jpg', $info->thumbnail);
    }

    public function test_only_existing_video_qualities_are_offered(): void
    {
        $keys = array_map(fn ($o) => $o->key, $this->build('youtube')->videoOptions());

        $this->assertSame(['video-best', 'video-1080', 'video-720', 'video-480', 'video-360'], $keys);
    }

    public function test_separate_streams_are_merged_with_matching_audio(): void
    {
        $option = $this->build('youtube')->option('video-1080');

        $this->assertStringStartsWith('137+bestaudio[ext=m4a]/', $option->selector);
        $this->assertStringContainsString('bestvideo[height<=?1080]+bestaudio', $option->selector);
        $this->assertSame('mp4', $option->mergeOutputFormat);
        $this->assertTrue($option->requiresFfmpeg);
        $this->assertSame(25_000_000 + 5_500_000, $option->filesize);
        $this->assertSame('1920x1080', $option->resolution);
        $this->assertSame('avc1', $option->videoCodec);
    }

    public function test_60fps_and_2k_4k_options(): void
    {
        $info   = $this->build('youtube-60fps');
        $labels = array_map(fn ($o) => $o->key . '=' . $o->label, $info->videoOptions());

        $this->assertSame([
            'video-best=Best (4K 2160p60)',
            'video-2160-60=4K (2160p60)',
            'video-2160=4K (2160p)',
            'video-1440-60=2K (1440p60)',
            'video-1080-60=1080p60',
            'video-1080=1080p',
            'video-720=720p',
        ], $labels);

        $this->assertStringStartsWith('299+bestaudio[ext=m4a]/bestvideo[height<=?1080][fps>30]+bestaudio', $info->option('video-1080-60')->selector);
        $this->assertStringStartsWith('137+bestaudio[ext=m4a]/bestvideo[height<=?1080][fps<=?30]+bestaudio', $info->option('video-1080')->selector);
        $this->assertSame(60.0, $info->option('video-2160-60')->fps);
        // Only one frame rate at 720p → no fps filter needed
        $this->assertStringNotContainsString('fps', $info->option('video-720')->selector);
    }

    public function test_720_prefers_mp4_over_webm(): void
    {
        $this->assertStringStartsWith('136+', $this->build('youtube')->option('video-720')->selector);
    }

    public function test_muxed_format_needs_no_merge(): void
    {
        $option = $this->build('youtube')->option('video-360');

        $this->assertStringStartsWith('18/', $option->selector);
        $this->assertNull($option->mergeOutputFormat);
        $this->assertFalse($option->requiresFfmpeg);
    }

    public function test_audio_options(): void
    {
        $info = $this->build('youtube');
        $keys = array_map(fn ($o) => $o->key, $info->audioOptions());

        $this->assertSame(['audio-best', 'audio-m4a', 'audio-mp3'], $keys);
        $this->assertSame('webm', $info->option('audio-best')->extension);
        $this->assertStringStartsWith('140/', $info->option('audio-m4a')->selector);
        $this->assertSame('mp3', $info->option('audio-mp3')->audioConvertTo);
    }

    public function test_portrait_video_is_bucketed_by_short_side(): void
    {
        $info = $this->build('tiktok', 'https://www.tiktok.com/@someone/video/7300000000000000000');
        $keys = array_map(fn ($o) => $o->key, $info->videoOptions());

        $this->assertSame('TikTok', $info->platform);
        $this->assertSame(['video-best', 'video-1080', 'video-480'], $keys);
        $this->assertStringContainsString('best[width<=?1080]', $info->option('video-1080')->selector);
    }

    public function test_muxed_only_media_extracts_audio_with_ffmpeg(): void
    {
        $option = $this->build('tiktok', 'https://www.tiktok.com/@a/video/1')->option('audio-best');

        $this->assertSame('best', $option->audioConvertTo);
        $this->assertSame('m4a', $option->extension);
        $this->assertTrue($option->requiresFfmpeg);
    }

    public function test_public_array_hides_selectors(): void
    {
        $json = json_encode($this->build('youtube')->toPublicArray());

        $this->assertStringNotContainsString('selector', $json);
        $this->assertStringNotContainsString('bestaudio', $json);
    }

    public function test_round_trips_through_cache_array(): void
    {
        $info = $this->build('youtube');

        $this->assertEquals($info, MediaInfo::fromArray($info->toArray()));
    }

    public function test_image_files_reported_as_formats_are_not_video(): void
    {
        $info = (new FormatOptionBuilder())->build('https://commons.wikimedia.org/wiki/File:Example.jpg', [
            'title'   => 'Example',
            'formats' => [['format_id' => '0', 'ext' => 'jpg', 'url' => 'https://upload.wikimedia.org/a.jpg', 'width' => 172, 'height' => 178]],
        ]);

        $this->assertSame([], $info->options); // → analysis falls back to gallery-dl for images
    }

    public function test_unsafe_format_ids_are_not_pinned(): void
    {
        $info = (new FormatOptionBuilder())->build('https://example.com/v', [
            'title'   => 'x',
            'formats' => [['format_id' => 'bad/id+[x]', 'ext' => 'mp4', 'height' => 720, 'width' => 1280, 'vcodec' => 'h264', 'acodec' => 'aac']],
        ]);

        $this->assertStringNotContainsString('bad', $info->option('video-720')->selector);
    }
}
