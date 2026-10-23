<?php

namespace ME\Utility\Services\MediaDownloader;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ME\Utility\Services\MediaDownloader\Exceptions\MediaDownloaderException;

/**
 * Backup for Facebook videos / reels when yt-dlp's extractor fails with "Cannot parse data"
 * (Facebook changes its page format often). yt-dlp still fetches the page fine — with the
 * user's login cookies the page embeds the DASH streams in "all_video_dash_prefetch_representations"
 * — so we let yt-dlp fetch it (--write-pages), read the streams ourselves, and hand yt-dlp an
 * info dict (--load-info-json) to download and merge as usual.
 */
class FacebookFallback
{
    private const STREAMS_KEY = '"all_video_dash_prefetch_representations":';

    public function __construct(private readonly YtDlp $ytDlp)
    {
    }

    public static function matches(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host === 'fb.watch' || $host === 'facebook.com' || str_ends_with($host, '.facebook.com');
    }

    public static function shouldTry(MediaDownloaderException $e): bool
    {
        // ErrorClassifier files Facebook's "Cannot parse data" under LOGIN_REQUIRED (it is often
        // friends-only media); with the user's cookies the page may still carry the streams.
        return in_array($e->reason, [MediaDownloaderException::SITE_CHANGED, MediaDownloaderException::EXTRACTION_FAILED], true)
            || ($e->reason === MediaDownloaderException::LOGIN_REQUIRED && str_contains(strtolower((string) $e->technical), 'cannot parse data'));
    }

    /**
     * yt-dlp-style info dict (id, title, formats…) for the video at $url.
     *
     * @param string $workDirectory scratch directory for the fetched pages; removed afterwards
     */
    public function info(string $url, ?string $cookiesFile, string $workDirectory): array
    {
        File::ensureDirectoryExists($workDirectory, 0770);

        try {
            $videoId = self::videoId($url);

            foreach ($this->ytDlp->writePages($url, $cookiesFile, $workDirectory) as $page) {
                $info = self::parse((string) file_get_contents($page), $videoId, $url);
                if ($info !== null) {
                    return $info;
                }
            }
        } finally {
            File::deleteDirectory($workDirectory);
        }

        throw MediaDownloaderException::because(MediaDownloaderException::SITE_CHANGED, "Facebook fallback: no video streams in the page for {$url}");
    }

    /** Numeric video id from reel / watch / videos links; null for share links (fb.watch…) */
    public static function videoId(string $url): ?string
    {
        return preg_match('#(?:/reels?/|/videos/(?:[^/?\#]+/)?|[?&]v=|/watch/\?v=)(\d{6,})#', $url, $m) ? $m[1] : null;
    }

    /**
     * Streams for $videoId (or the first video on the page when unknown), or null.
     */
    public static function parse(string $html, ?string $videoId, string $url): ?array
    {
        $item = null;

        for ($offset = 0; ($pos = strpos($html, self::STREAMS_KEY, $offset)) !== false; $offset = $pos + 1) {
            $json = self::jsonValueAt($html, $pos + strlen(self::STREAMS_KEY));
            $list = $json !== null ? json_decode($json, true) : null;

            foreach (is_array($list) ? $list : [] as $candidate) {
                if (! is_array($candidate) || empty($candidate['representations'])) {
                    continue;
                }
                if ($videoId === null || (string) ($candidate['video_id'] ?? '') === $videoId) {
                    $item = $candidate;
                    break 2;
                }
            }
        }

        if ($item === null) {
            return null;
        }

        $id      = (string) ($item['video_id'] ?? $videoId ?? 'facebook');
        $formats = [];

        foreach ($item['representations'] as $i => $rep) {
            $streamUrl = $rep['base_url'] ?? null;
            if (! is_string($streamUrl) || ! preg_match('#^https://[^/]+\.(fbcdn\.net|facebook\.com)/#', $streamUrl)) {
                continue;
            }

            $mime = (string) ($rep['mime_type'] ?? '');
            $kbps = isset($rep['bandwidth']) ? round($rep['bandwidth'] / 1000, 1) : null;

            $format = [
                'format_id' => 'fb-' . ($rep['representation_id'] ?? $i),
                'url'       => $streamUrl,
                'ext'       => 'mp4',
                'protocol'  => 'https',
            ];

            if (str_starts_with($mime, 'audio/')) {
                $formats[] = $format + ['vcodec' => 'none', 'acodec' => $rep['codecs'] ?? 'mp4a', 'abr' => $kbps, 'tbr' => $kbps];
            } elseif (str_starts_with($mime, 'video/')) {
                $formats[] = $format + [
                    'vcodec' => $rep['codecs'] ?? 'unknown',
                    'acodec' => 'none',
                    'width'  => isset($rep['width']) ? (int) $rep['width'] : null,
                    'height' => isset($rep['height']) ? (int) $rep['height'] : null,
                    'vbr'    => $kbps,
                    'tbr'    => $kbps,
                ];
            }
        }

        if ($formats === []) {
            return null;
        }

        $durationMs = self::firstMatch($html, '/"playable_duration_in_ms":(\d+)/');
        $caption    = self::firstMatch($html, '/"message":\{"text":"((?:[^"\\\\]|\\\\.){1,600})"/');

        return [
            'id'            => $id,
            'title'         => $caption !== null ? Str::limit(self::unescape($caption), 120) : "Facebook video {$id}",
            'extractor'     => 'facebook',
            'extractor_key' => 'Facebook',
            'webpage_url'   => $url,
            'duration'      => $durationMs !== null ? round(((int) $durationMs) / 1000, 1) : null,
            'thumbnail'     => ($thumb = self::firstMatch($html, '/"preferred_thumbnail":\{"image":\{"uri":"((?:[^"\\\\]|\\\\.)+)"/')) !== null ? self::unescape($thumb) : null,
            'formats'       => $formats,
        ];
    }

    /** The complete JSON value (array/object) starting at $offset, matching brackets outside strings. */
    private static function jsonValueAt(string $s, int $offset): ?string
    {
        $open = $s[$offset] ?? '';
        if ($open !== '[' && $open !== '{') {
            return null;
        }

        $depth    = 0;
        $inString = false;
        $length   = strlen($s);

        for ($i = $offset; $i < $length; $i++) {
            $c = $s[$i];

            if ($inString) {
                if ($c === '\\') {
                    $i++;
                } elseif ($c === '"') {
                    $inString = false;
                }
                continue;
            }

            if ($c === '"') {
                $inString = true;
            } elseif ($c === '[' || $c === '{') {
                $depth++;
            } elseif (($c === ']' || $c === '}') && --$depth === 0) {
                return substr($s, $offset, $i - $offset + 1);
            }
        }

        return null;
    }

    private static function firstMatch(string $html, string $pattern): ?string
    {
        return preg_match($pattern, $html, $m) ? $m[1] : null;
    }

    private static function unescape(string $jsonString): string
    {
        return (string) (json_decode('"' . $jsonString . '"') ?? stripslashes($jsonString));
    }
}
