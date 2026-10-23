<?php

namespace ME\Utility\Services\MediaDownloader\Data;

/**
 * Normalized, user-safe view of yt-dlp metadata. Raw yt-dlp JSON never leaves the server.
 */
final class MediaInfo
{
    /**
     * @param MediaFormatOption[] $options
     */
    public function __construct(
        public readonly string $url,
        public readonly string $title,
        public readonly string $platform,
        public readonly ?string $uploader,
        public readonly ?int $duration,
        public readonly ?string $thumbnail,
        public readonly ?string $description,
        public readonly array $options,
    ) {
    }

    public function option(string $key): ?MediaFormatOption
    {
        foreach ($this->options as $option) {
            if ($option->key === $key) {
                return $option;
            }
        }

        return null;
    }

    /** @return MediaFormatOption[] */
    public function videoOptions(): array
    {
        return array_values(array_filter($this->options, fn (MediaFormatOption $o) => $o->type === MediaFormatOption::TYPE_VIDEO));
    }

    /** @return MediaFormatOption[] */
    public function audioOptions(): array
    {
        return array_values(array_filter($this->options, fn (MediaFormatOption $o) => $o->isAudio()));
    }

    /** @return MediaFormatOption[] */
    public function imageOptions(): array
    {
        return array_values(array_filter($this->options, fn (MediaFormatOption $o) => $o->isImage()));
    }

    public function durationFormatted(): ?string
    {
        if ($this->duration === null) {
            return null;
        }

        $h = intdiv($this->duration, 3600);
        $m = intdiv($this->duration % 3600, 60);
        $s = $this->duration % 60;

        return $h > 0 ? sprintf('%d:%02d:%02d', $h, $m, $s) : sprintf('%02d:%02d', $m, $s);
    }

    public function toPublicArray(): array
    {
        return [
            'url'         => $this->url,
            'title'       => $this->title,
            'platform'    => $this->platform,
            'uploader'    => $this->uploader,
            'duration'    => $this->durationFormatted(),
            'thumbnail'   => $this->thumbnail,
            'description' => $this->description,
            'formats'     => [
                'video' => array_map(fn (MediaFormatOption $o) => $o->toPublicArray(), $this->videoOptions()),
                'audio' => array_map(fn (MediaFormatOption $o) => $o->toPublicArray(), $this->audioOptions()),
                'image' => array_map(fn (MediaFormatOption $o) => $o->toPublicArray(), $this->imageOptions()),
            ],
        ];
    }

    /** Full representation for caching (includes selectors). */
    public function toArray(): array
    {
        return [
            'url'         => $this->url,
            'title'       => $this->title,
            'platform'    => $this->platform,
            'uploader'    => $this->uploader,
            'duration'    => $this->duration,
            'thumbnail'   => $this->thumbnail,
            'description' => $this->description,
            'options'     => array_map(fn (MediaFormatOption $o) => $o->toArray(), $this->options),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            url: $data['url'],
            title: $data['title'],
            platform: $data['platform'],
            uploader: $data['uploader'],
            duration: $data['duration'],
            thumbnail: $data['thumbnail'],
            description: $data['description'],
            options: array_map(fn (array $o) => MediaFormatOption::fromArray($o), $data['options']),
        );
    }
}
