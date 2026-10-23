<?php

namespace ME\Utility\Tests\Unit;

use ME\Utility\Services\MediaDownloader\ErrorClassifier;
use ME\Utility\Services\MediaDownloader\FacebookFallback;
use ME\Utility\Services\MediaDownloader\FormatOptionBuilder;
use PHPUnit\Framework\TestCase;

class FacebookFallbackTest extends TestCase
{
    private const URL = 'https://www.facebook.com/reel/1454196170148536';

    /** Trimmed from a logged-in reel page that yt-dlp 2026.10 reported as "Cannot parse data" */
    private function page(): string
    {
        $streams = json_encode([[
            'video_id'        => '1454196170148536',
            'representations' => [
                ['representation_id' => '11v', 'mime_type' => 'video/mp4', 'codecs' => 'vp09.00.21.08', 'base_url' => 'https://scontent.xx.fbcdn.net/v/low.mp4', 'bandwidth' => 453247, 'width' => 356, 'height' => 640],
                ['representation_id' => '22v', 'mime_type' => 'video/mp4', 'codecs' => 'vp09.00.31.08', 'base_url' => 'https://scontent.xx.fbcdn.net/v/high.mp4', 'bandwidth' => 1947268, 'width' => 708, 'height' => 1274],
                ['representation_id' => '33a', 'mime_type' => 'audio/mp4', 'codecs' => 'mp4a.40.5', 'base_url' => 'https://scontent.xx.fbcdn.net/v/audio.mp4', 'bandwidth' => 68045],
                ['representation_id' => '44v', 'mime_type' => 'video/mp4', 'codecs' => 'avc1', 'base_url' => 'https://evil.example.com/x.mp4', 'bandwidth' => 1, 'width' => 1, 'height' => 1],
            ],
        ]]);

        return '<html><title>Facebook</title><script>{"other_video":{"all_video_dash_prefetch_representations":[{"video_id":"999","representations":[]}]}}'
            . '{"all_video_dash_prefetch_representations":' . $streams . ',"x":"]}"}'
            . '{"playable_duration_in_ms":15042,"preferred_thumbnail":{"image":{"uri":"https:\/\/scontent.xx.fbcdn.net\/thumb.jpg"}}}</script></html>';
    }

    public function test_finds_video_id_in_facebook_links(): void
    {
        $this->assertSame('1454196170148536', FacebookFallback::videoId(self::URL));
        $this->assertSame('123456789', FacebookFallback::videoId('https://www.facebook.com/watch/?v=123456789'));
        $this->assertSame('123456789', FacebookFallback::videoId('https://www.facebook.com/somepage/videos/123456789/'));
        $this->assertNull(FacebookFallback::videoId('https://fb.watch/abcDEF/'));

        $this->assertTrue(FacebookFallback::matches('https://m.facebook.com/reel/1'));
        $this->assertTrue(FacebookFallback::matches('https://fb.watch/abc/'));
        $this->assertFalse(FacebookFallback::matches('https://notfacebook.com/reel/1'));
    }

    public function test_reads_dash_streams_from_the_page(): void
    {
        $raw = FacebookFallback::parse($this->page(), '1454196170148536', self::URL);

        $this->assertSame('1454196170148536', $raw['id']);
        $this->assertSame(15.0, $raw['duration']);
        $this->assertSame('https://scontent.xx.fbcdn.net/thumb.jpg', $raw['thumbnail']);
        // Streams on other hosts are ignored
        $this->assertSame(['fb-11v', 'fb-22v', 'fb-33a'], array_column($raw['formats'], 'format_id'));

        $info = (new FormatOptionBuilder())->build(self::URL, $raw);
        $this->assertSame('video-best', $info->videoOptions()[0]->key);
        $this->assertStringStartsWith('fb-22v+', $info->videoOptions()[0]->selector);
        $this->assertNotSame([], $info->audioOptions());
    }

    public function test_fallback_runs_for_the_error_yt_dlp_really_reports(): void
    {
        // Exact stderr from yt-dlp 2026.08.19 / nightly 2026.10.10 for the reel above
        $stderr = 'ERROR: [facebook] 1454196170148536: Cannot parse data; please report this issue on  https://github.com/yt-dlp/yt-dlp/issues?q= , filling out the appropriate issue template. Confirm you are on the latest version using  yt-dlp -U';
        $e = (new ErrorClassifier())->exception($stderr, 1);

        $this->assertTrue(FacebookFallback::shouldTry($e), "not tried for reason {$e->reason}");
        $this->assertFalse(FacebookFallback::shouldTry((new ErrorClassifier())->exception('ERROR: [facebook] x: This video is private', 1)));
    }

    public function test_no_streams_for_the_video_returns_null(): void
    {
        $this->assertNull(FacebookFallback::parse($this->page(), '555', self::URL));
        $this->assertNull(FacebookFallback::parse('<html>login</html>', null, self::URL));
    }
}
