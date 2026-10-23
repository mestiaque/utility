<?php

namespace ME\Utility\Services\MediaDownloader;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use ME\Utility\Services\MediaDownloader\Data\PlaylistInfo;
use ME\Utility\Services\MediaDownloader\Exceptions\MediaDownloaderException;

/**
 * Lists the videos of a public Telegram channel (t.me/<name>), which yt-dlp cannot do: it only
 * understands single posts (t.me/<name>/<id>). Reads the channel's public web preview
 * (t.me/s/<name>, ~20 posts per page, newest last) and pages back with ?before=<id>.
 * Each entry is a post link, downloaded later by yt-dlp's telegram:embed extractor.
 */
class TelegramChannel
{
    private const CHANNEL_URL = '#^https?://(?:www\.)?(?:t|telegram)\.me/(?:s/)?([A-Za-z][A-Za-z0-9_]{3,31})/?(?:[?\#].*)?$#i';

    /** Seconds spent paging back through a channel before returning what was found so far
     *  (keeps the analyze request inside PHP's / the web server's request time limit) */
    private const TIME_BUDGET = 20;

    /** t.me paths that look like a channel name but are not one */
    private const RESERVED = ['joinchat', 'addstickers', 'addemoji', 'addlist', 'share', 'proxy', 'socks', 'login', 'setlanguage', 'contact', 'boost', 'iv'];

    public function __construct(
        private readonly int $maxItems,
        private readonly int $timeout,
    ) {
    }

    public static function matches(string $url): bool
    {
        return preg_match(self::CHANNEL_URL, trim($url), $m) === 1
            && ! in_array(strtolower($m[1]), self::RESERVED, true);
    }

    public function list(string $url): PlaylistInfo
    {
        preg_match(self::CHANNEL_URL, trim($url), $m);
        $name = $m[1];

        $entries   = [];
        $seen      = [];
        $title     = null;
        $skipped   = 0;
        $posts     = 0;
        $before    = null;
        $truncated = false;
        $deadline  = microtime(true) + self::TIME_BUDGET;

        // Pages also hold text/photo posts, so allow a few more pages than items/20
        for ($page = 0, $maxPages = intdiv($this->maxItems, 10) + 3; $page < $maxPages; $page++) {
            $html = $this->fetch("https://t.me/s/{$name}" . ($before !== null ? "?before={$before}" : ''));
            $xpath = $this->xpath($html);

            $title ??= $this->text($xpath, '//div[contains(@class,"tgme_channel_info_header_title")]');

            $pagePosts = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " tgme_widget_message ") and @data-post]');
            if ($pagePosts->length === 0) {
                break;
            }
            $posts += $pagePosts->length;

            // Newest first, like other channel listings
            foreach (array_reverse(iterator_to_array($pagePosts)) as $post) {
                foreach ($this->videos($xpath, $post, $name) as $video) {
                    if ($video === null) {
                        $skipped++;
                        continue;
                    }
                    if (isset($seen[$video['url']])) {
                        continue;
                    }
                    $seen[$video['url']] = true;

                    if (count($entries) >= $this->maxItems) {
                        $truncated = true;
                        break 3;
                    }

                    $video['id'] = (string) count($entries);
                    $entries[]   = $video;
                }
            }

            $before = $this->olderPageCursor($xpath);
            if ($before === null || microtime(true) > $deadline) {
                break;
            }
        }

        if ($posts === 0) {
            // Private channels and bots have no web preview: t.me/s/<name> redirects to the join page
            throw MediaDownloaderException::because(MediaDownloaderException::TELEGRAM_APP_ONLY, "No public posts at t.me/s/{$name}");
        }

        if ($entries === [] && $skipped > 0) {
            throw MediaDownloaderException::because(MediaDownloaderException::TELEGRAM_APP_ONLY, "{$skipped} videos at t.me/s/{$name} are not playable on the web");
        }

        return new PlaylistInfo(
            url: $url,
            title: Str::limit($title ?: "@{$name}", 300),
            platform: 'Telegram',
            uploader: '@' . $name,
            entries: $entries,
            truncated: $truncated || $before !== null,
        );
    }

    /**
     * One entry per video in a post (albums can hold several); null for a video the web preview
     * cannot play (too big — "VIEW IN TELEGRAM" only), which yt-dlp cannot fetch either.
     *
     * @return array<int, ?array>
     */
    private function videos(DOMXPath $xpath, DOMElement $post, string $name): array
    {
        $postId = (string) $post->getAttribute('data-post');
        $text   = $this->text($xpath, './/div[contains(@class,"tgme_widget_message_text")]', $post);
        $videos = [];

        $players = $xpath->query('.//a[contains(@class,"tgme_widget_message_video_player")]', $post);
        foreach ($players as $i => $player) {
            $playable = $xpath->query('.//video[@src]', $player)->length > 0;
            if (! $playable) {
                $videos[] = null;
                continue;
            }

            // Album items link to their own message id ("…/532?single"); yt-dlp wants the bare post URL
            $href = (string) $player->getAttribute('href');
            $url  = preg_match('#^https://t\.me/' . preg_quote($name, '#') . '/(\d+)#i', $href, $m)
                ? "https://t.me/{$name}/{$m[1]}"
                : "https://t.me/{$postId}";

            $title = $text ? Str::of($text)->squish()->limit(120)->toString() : "Post {$postId}";

            $videos[] = [
                'kind'      => PlaylistInfo::KIND_VIDEO,
                'media'     => PlaylistInfo::MEDIA_VIDEO,
                'url'       => $url,
                'title'     => $players->length > 1 ? $title . ' (' . ($i + 1) . '/' . $players->length . ')' : $title,
                'duration'  => $this->duration($this->text($xpath, './/time[contains(@class,"message_video_duration")]', $player)),
                'thumbnail' => $this->thumbnail($xpath, $player),
            ];
        }

        return $videos;
    }

    private function olderPageCursor(DOMXPath $xpath): ?string
    {
        $prev = $xpath->query('//link[@rel="prev"]/@href')->item(0)?->nodeValue;

        return $prev !== null && preg_match('/[?&]before=(\d+)/', $prev, $m) ? $m[1] : null;
    }

    private function thumbnail(DOMXPath $xpath, DOMElement $player): ?string
    {
        $style = (string) $xpath->query('.//i[contains(@class,"tgme_widget_message_video_thumb")]/@style', $player)->item(0)?->nodeValue;

        return preg_match("#url\\('(https://[^']+)'\\)#", $style, $m) ? $m[1] : null;
    }

    /** "1:02:03" / "4:05" → seconds */
    private function duration(?string $text): ?int
    {
        if ($text === null || ! preg_match('/^\d+(:\d{1,2}){1,2}$/', $text)) {
            return null;
        }

        return array_reduce(explode(':', $text), fn (int $carry, string $part) => $carry * 60 + (int) $part, 0);
    }

    private function fetch(string $url): string
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0 Safari/537.36'])
                ->get($url);
        } catch (ConnectionException $e) {
            throw MediaDownloaderException::because(MediaDownloaderException::NETWORK_ERROR, $e->getMessage(), $e);
        }

        if (in_array($response->status(), [403, 429], true)) {
            throw MediaDownloaderException::because(MediaDownloaderException::BLOCKED_BY_SITE, "HTTP {$response->status()} from {$url}");
        }

        if (! $response->successful()) {
            throw MediaDownloaderException::because(MediaDownloaderException::NETWORK_ERROR, "HTTP {$response->status()} from {$url}");
        }

        return $response->body();
    }

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($dom);
    }

    private function text(DOMXPath $xpath, string $query, ?DOMElement $context = null): ?string
    {
        $node = $xpath->query($query, $context)->item(0);
        $text = $node !== null ? trim(preg_replace('/\s+/u', ' ', $node->textContent)) : '';

        return $text !== '' ? $text : null;
    }
}
