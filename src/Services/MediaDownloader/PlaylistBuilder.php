<?php

namespace ME\Utility\Services\MediaDownloader;

use Illuminate\Support\Str;
use ME\Utility\Services\MediaDownloader\Data\PlaylistInfo;

/**
 * Normalizes yt-dlp "--flat-playlist" output (playlists, channels, profiles, multi-video pages).
 */
class PlaylistBuilder
{
    public function __construct(private readonly FormatOptionBuilder $formats)
    {
    }

    public static function isPlaylist(array $raw): bool
    {
        return ($raw['_type'] ?? null) === 'playlist' || (isset($raw['entries']) && is_array($raw['entries']));
    }

    public function build(string $url, array $raw, int $limit): PlaylistInfo
    {
        $entries = [];

        foreach (array_values(array_filter($raw['entries'] ?? [], 'is_array')) as $i => $entry) {
            $entryUrl = $this->entryUrl($entry);
            if ($entryUrl === null) {
                continue;
            }

            $nested = $this->isNestedList($entry);

            $entries[] = [
                'id'        => (string) $i,
                'kind'      => $nested ? PlaylistInfo::KIND_PLAYLIST : PlaylistInfo::KIND_VIDEO,
                'media'     => $nested ? null : $this->mediaKind($entry, $entryUrl),
                'url'       => $entryUrl,
                'title'     => Str::limit(trim((string) ($entry['title'] ?? '')) ?: 'Item ' . ($i + 1), 300),
                'duration'  => isset($entry['duration']) && is_numeric($entry['duration']) ? (int) round($entry['duration']) : null,
                'thumbnail' => $this->thumbnail($entry),
            ];
        }

        return new PlaylistInfo(
            url: $url,
            title: Str::limit(trim((string) ($raw['title'] ?? '')) ?: 'Untitled list', 300),
            platform: $this->formats->platform($raw, $url),
            uploader: is_string($raw['uploader'] ?? $raw['channel'] ?? null) ? ($raw['uploader'] ?? $raw['channel']) : null,
            entries: $entries,
            truncated: count($raw['entries'] ?? []) >= $limit,
        );
    }

    /**
     * What an item most likely contains, from the flat listing alone (no extra requests):
     * "images" (photo post), "video", or null when it can't be told.
     */
    private function mediaKind(array $entry, string $url): ?string
    {
        if (preg_match('#/photos?/#i', $url)) {
            return PlaylistInfo::MEDIA_IMAGES;
        }

        $isTikTok = strcasecmp((string) ($entry['ie_key'] ?? ''), 'TikTok') === 0 || str_contains((string) parse_url($url, PHP_URL_HOST), 'tiktok.com');

        $thumbIds = array_column(array_filter($entry['thumbnails'] ?? [], 'is_array'), 'id');

        if ($isTikTok && $thumbIds !== []) {
            // TikTok gives every video an animated ("dynamic") cover; photo posts never have one.
            // Duration is no signal: photo posts with music report the song's length.
            return in_array('dynamicCover', $thumbIds, true) ? PlaylistInfo::MEDIA_VIDEO : PlaylistInfo::MEDIA_IMAGES;
        }

        return isset($entry['duration']) ? PlaylistInfo::MEDIA_VIDEO : null;
    }

    private function isNestedList(array $entry): bool
    {
        if (($entry['_type'] ?? null) === 'playlist') {
            return true;
        }

        // Flat "url" entries pointing at another list extractor (tabs, playlists, user pages)
        return ($entry['_type'] ?? null) === 'url'
            && preg_match('/(Tab|Playlist|User|Channel|Album)$/', (string) ($entry['ie_key'] ?? '')) === 1;
    }

    private function entryUrl(array $entry): ?string
    {
        foreach (['webpage_url', 'url', 'original_url'] as $key) {
            $value = $entry[$key] ?? null;
            if (is_string($value) && preg_match('#^https?://#i', $value)) {
                return $value;
            }
        }

        return null;
    }

    private function thumbnail(array $entry): ?string
    {
        if (is_string($entry['thumbnail'] ?? null) && preg_match('#^https?://#i', $entry['thumbnail'])) {
            return $entry['thumbnail'];
        }

        // yt-dlp lists thumbnails from worst to best; a mid-sized one is plenty for a list row
        $urls = array_values(array_filter(array_map(
            fn ($t) => is_array($t) && is_string($t['url'] ?? null) && preg_match('#^https?://#i', $t['url']) ? $t['url'] : null,
            $entry['thumbnails'] ?? []
        )));

        return $urls[intdiv(count($urls), 2)] ?? null;
    }
}
