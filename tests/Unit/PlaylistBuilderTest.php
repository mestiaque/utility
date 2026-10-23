<?php

namespace ME\Utility\Tests\Unit;

use ME\Utility\Services\MediaDownloader\FormatOptionBuilder;
use ME\Utility\Services\MediaDownloader\PlaylistBuilder;
use PHPUnit\Framework\TestCase;

class PlaylistBuilderTest extends TestCase
{
    public function test_marks_photo_posts_and_videos_in_a_profile(): void
    {
        // Trimmed from a real yt-dlp --flat-playlist TikTok profile listing
        $raw  = json_decode(file_get_contents(__DIR__ . '/../Fixtures/tiktok-profile.json'), true);
        $list = (new PlaylistBuilder(new FormatOptionBuilder()))->build('https://www.tiktok.com/@example', $raw, 200);

        // video: animated cover · photo post without music · photo post WITH music (has a
        // duration, but no animated cover) · "/photos/" URL on another site
        $this->assertSame(['video', 'images', 'images', 'images'], array_column($list->entries, 'media'));
        $this->assertSame(23, $list->entries[2]['duration']);

        $public = $list->toPublicArray();
        $this->assertSame(3, $public['photos']);
        $this->assertSame('images', $public['entries'][1]['media']);
    }
}
