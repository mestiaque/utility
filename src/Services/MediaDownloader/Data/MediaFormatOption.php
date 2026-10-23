<?php

namespace ME\Utility\Services\MediaDownloader\Data;

/**
 * One downloadable choice shown to the user (e.g. "video-1080", "audio-mp3").
 * The yt-dlp selector stays server-side; only the key travels to the browser.
 */
final class MediaFormatOption
{
    public const TYPE_VIDEO = 'video';
    public const TYPE_AUDIO = 'audio';
    public const TYPE_IMAGE = 'image';

    public function __construct(
        public readonly string $key,
        public readonly string $type,
        public readonly string $label,
        public readonly string $extension,
        public readonly string $selector,
        public readonly ?string $resolution = null,
        public readonly ?float $fps = null,
        public readonly ?string $videoCodec = null,
        public readonly ?string $audioCodec = null,
        public readonly ?int $filesize = null,
        public readonly bool $filesizeApproximate = true,
        public readonly ?string $audioConvertTo = null,
        public readonly ?string $mergeOutputFormat = null,
        public readonly bool $requiresFfmpeg = false,
        /** Image options: preview URL of the image, and gallery-dl --range positions (null = all) */
        public readonly ?string $preview = null,
        public readonly ?array $positions = null,
    ) {
    }

    public function isAudio(): bool
    {
        return $this->type === self::TYPE_AUDIO;
    }

    public function isImage(): bool
    {
        return $this->type === self::TYPE_IMAGE;
    }

    /** Data safe to send to the browser (no selector). */
    public function toPublicArray(): array
    {
        return [
            'key'          => $this->key,
            'type'         => $this->type,
            'label'        => $this->label,
            'extension'    => strtoupper($this->extension),
            'resolution'   => $this->resolution,
            'fps'          => $this->fps,
            'video_codec'  => $this->videoCodec,
            'audio_codec'  => $this->audioCodec,
            'filesize'     => $this->filesize,
            'preview'      => $this->preview,
            'filesize_human' => $this->filesize ? self::humanBytes($this->filesize, $this->filesizeApproximate) : null,
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

    public static function humanBytes(int $bytes, bool $approximate = false): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i     = $bytes > 0 ? min((int) floor(log($bytes, 1024)), count($units) - 1) : 0;
        $value = $bytes / (1024 ** $i);

        return ($approximate ? '~' : '') . round($value, $i >= 2 ? 1 : 0) . ' ' . $units[$i];
    }
}
