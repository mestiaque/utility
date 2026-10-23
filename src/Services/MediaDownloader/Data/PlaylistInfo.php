<?php

namespace ME\Utility\Services\MediaDownloader\Data;

/**
 * A link that contains many items: playlist, channel, profile, or a page with several videos.
 * Entries are addressed by their index ("id"), so the browser never sends URLs back.
 */
final class PlaylistInfo
{
    public const KIND_VIDEO    = 'video';
    public const KIND_PLAYLIST = 'playlist'; // nested list, e.g. a channel's "Videos" / "Shorts" tab

    /** Best guess of what a downloadable entry contains (null = unknown) */
    public const MEDIA_VIDEO  = 'video';
    public const MEDIA_IMAGES = 'images';

    /**
     * @param array<int, array{id: string, kind: string, url: string, title: string, duration: ?int, thumbnail: ?string}> $entries
     */
    public function __construct(
        public readonly string $url,
        public readonly string $title,
        public readonly string $platform,
        public readonly ?string $uploader,
        public readonly array $entries,
        public readonly bool $truncated,
    ) {
    }

    public function entry(string $id): ?array
    {
        foreach ($this->entries as $entry) {
            if ($entry['id'] === $id) {
                return $entry;
            }
        }

        return null;
    }

    public function videoCount(): int
    {
        return count(array_filter($this->entries, fn (array $e) => $e['kind'] === self::KIND_VIDEO));
    }

    public function toPublicArray(): array
    {
        return [
            'url'       => $this->url,
            'title'     => $this->title,
            'platform'  => $this->platform,
            'uploader'  => $this->uploader,
            'truncated' => $this->truncated,
            'count'     => $this->videoCount(),
            'photos'    => count(array_filter($this->entries, fn (array $e) => ($e['media'] ?? null) === self::MEDIA_IMAGES)),
            'entries'   => array_map(fn (array $e) => [
                'id'        => $e['id'],
                'kind'      => $e['kind'],
                'media'     => $e['media'] ?? null,
                'title'     => $e['title'],
                'duration'  => $e['duration'] !== null ? self::formatDuration($e['duration']) : null,
                'thumbnail' => $e['thumbnail'],
                // Nested lists are opened by analyzing their URL, so the browser needs it
                'url'       => $e['kind'] === self::KIND_PLAYLIST ? $e['url'] : null,
            ], $this->entries),
        ];
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public static function fromArray(array $data): self
    {
        return new self(...$data);
    }

    private static function formatDuration(int $seconds): string
    {
        return $seconds >= 3600
            ? sprintf('%d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60)
            : sprintf('%02d:%02d', intdiv($seconds, 60), $seconds % 60);
    }
}
