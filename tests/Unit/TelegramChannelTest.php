<?php

namespace ME\Utility\Tests\Unit;

use Illuminate\Support\Facades\Http;
use ME\Utility\Services\MediaDownloader\Exceptions\MediaDownloaderException;
use ME\Utility\Services\MediaDownloader\FormatOptionBuilder;
use ME\Utility\Services\MediaDownloader\TelegramChannel;
use ME\Utility\Tests\TestCase;

class TelegramChannelTest extends TestCase
{
    /** Trimmed t.me/s/<channel> markup: one page of posts, oldest first */
    private function page(array $posts, ?int $before): string
    {
        $prev = $before !== null ? '<link rel="prev" href="/s/example?before=' . $before . '">' : '';

        return '<html><head>' . $prev . '</head><body>'
            . '<div class="tgme_channel_info_header_title"><span dir="auto">Example Channel</span></div>'
            . implode('', $posts) . '</body></html>';
    }

    private function tgPost(int $id, string $text, array $videos): string
    {
        $players = implode('', array_map(fn (array $v) => '<a class="tgme_widget_message_video_player js-message_video_player" href="https://t.me/example/' . $v[0] . ($v[1] ? '?single' : '') . '">'
            . '<i class="tgme_widget_message_video_thumb" style="background-image:url(\'https://cdn4.telesco.pe/file/thumb' . $v[0] . '.jpg\')"></i>'
            . ($v[2] ? '<div class="tgme_widget_message_video_wrap"><video src="https://cdn4.telesco.pe/file/v.mp4"></video></div>' : '<div class="message_media_not_supported">Media is too big</div>')
            . '<time class="message_video_duration js-message_video_duration">1:05</time></a>', $videos));

        return '<div class="tgme_widget_message_wrap"><div class="tgme_widget_message js-widget_message" data-post="example/' . $id . '">'
            . $players . '<div class="tgme_widget_message_text">' . $text . '</div></div></div>';
    }

    public function test_recognises_channel_links_only(): void
    {
        $this->assertTrue(TelegramChannel::matches('https://t.me/example'));
        $this->assertTrue(TelegramChannel::matches('https://t.me/s/example/'));
        $this->assertTrue(TelegramChannel::matches('https://telegram.me/example?x=1'));

        $this->assertFalse(TelegramChannel::matches('https://t.me/example/531'));   // single post → yt-dlp
        $this->assertFalse(TelegramChannel::matches('https://t.me/joinchat/abcdef'));
        $this->assertFalse(TelegramChannel::matches('https://example.com/example'));
    }

    public function test_lists_playable_videos_newest_first_across_pages(): void
    {
        Http::fake([
            't.me/s/example?before=10' => Http::response($this->page([
                $this->tgPost(8, 'Old clip', [[8, false, true]]),
            ], null)),
            't.me/s/example' => Http::response($this->page([
                $this->tgPost(10, 'Text only', []),
                $this->tgPost(11, 'Album', [[11, true, true], [12, true, true]]),
                $this->tgPost(13, 'Huge', [[13, false, false]]),
                $this->tgPost(14, 'Newest', [[14, false, true]]),
            ], 10)),
        ]);

        $list = (new TelegramChannel(maxItems: 50, timeout: 5))->list('https://t.me/example');

        $this->assertSame('Example Channel', $list->title);
        $this->assertSame('Telegram', $list->platform);
        $this->assertSame(
            ['https://t.me/example/14', 'https://t.me/example/11', 'https://t.me/example/12', 'https://t.me/example/8'],
            array_column($list->entries, 'url'),
        );
        $this->assertSame(['0', '1', '2', '3'], array_column($list->entries, 'id'));
        $this->assertSame('Album (1/2)', $list->entries[1]['title']);
        $this->assertSame(65, $list->entries[0]['duration']);
        $this->assertSame('https://cdn4.telesco.pe/file/thumb14.jpg', $list->entries[0]['thumbnail']);
        $this->assertFalse($list->truncated);
    }

    public function test_stops_at_the_item_limit(): void
    {
        Http::fake(['t.me/s/example*' => Http::response($this->page([
            $this->tgPost(1, 'a', [[1, false, true]]),
            $this->tgPost(2, 'b', [[2, false, true]]),
        ], 1))]);

        $list = (new TelegramChannel(maxItems: 1, timeout: 5))->list('https://t.me/example');

        $this->assertCount(1, $list->entries);
        $this->assertTrue($list->truncated);
    }

    public function test_channel_without_web_playable_videos_explains_why(): void
    {
        Http::fake(['t.me/s/example*' => Http::response($this->page([
            $this->tgPost(5, 'Huge', [[5, false, false]]),
        ], null))]);

        try {
            (new TelegramChannel(maxItems: 50, timeout: 5))->list('https://t.me/example');
            $this->fail('Expected an exception');
        } catch (MediaDownloaderException $e) {
            $this->assertSame(MediaDownloaderException::TELEGRAM_APP_ONLY, $e->reason);
        }
    }

    public function test_codecless_mp4_post_offers_a_video_option(): void
    {
        // yt-dlp telegram:embed: one format, no codec / size information
        $info = (new FormatOptionBuilder())->build('https://t.me/example/14', [
            'id' => '14', 'title' => 'Clip', 'extractor_key' => 'TelegramEmbed',
            'formats' => [['format_id' => '0', 'url' => 'https://cdn4.telesco.pe/file/v.mp4', 'ext' => 'mp4']],
        ]);

        $this->assertNotSame([], $info->videoOptions());
        $this->assertSame('video-best', $info->videoOptions()[0]->key);
    }
}
