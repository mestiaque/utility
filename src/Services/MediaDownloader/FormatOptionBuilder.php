<?php

namespace ME\Utility\Services\MediaDownloader;

use Illuminate\Support\Str;
use ME\Utility\Services\MediaDownloader\Data\MediaFormatOption;
use ME\Utility\Services\MediaDownloader\Data\MediaInfo;

/**
 * Turns raw yt-dlp JSON into a MediaInfo with only the options that actually exist.
 * Selectors are built dynamically, pinned to the analysed format id with generic fallbacks.
 */
class FormatOptionBuilder
{
    /** Quality buckets offered to the user, matched against the shorter side of the frame. */
    public const VIDEO_HEIGHTS = [2160, 1440, 1080, 720, 480, 360];

    /** A frame counts toward a bucket when its short side reaches this share of the bucket. */
    private const BUCKET_TOLERANCE = 0.85;

    private const MP3_BITRATE_KBPS = 192;

    /** Frame rates at or above this are offered as a separate "60 fps" choice. */
    private const HIGH_FPS = 48;

    private const RESOLUTION_NAMES = [2160 => '4K', 1440 => '2K'];

    private const PLATFORM_NAMES = [
        'youtube'   => 'YouTube',
        'youtubetab'=> 'YouTube',
        'tiktok'    => 'TikTok',
        'facebook'  => 'Facebook',
        'instagram' => 'Instagram',
        'twitter'   => 'X / Twitter',
        'pinterest' => 'Pinterest',
        'vimeo'     => 'Vimeo',
        'dailymotion' => 'Dailymotion',
        'reddit'    => 'Reddit',
        'soundcloud'=> 'SoundCloud',
    ];

    public function build(string $url, array $raw): MediaInfo
    {
        $duration = isset($raw['duration']) && is_numeric($raw['duration']) ? (int) round($raw['duration']) : null;
        $formats  = $this->usableFormats($raw);

        return new MediaInfo(
            url: $url,
            title: Str::limit(trim((string) ($raw['title'] ?? $raw['fulltitle'] ?? 'Untitled')) ?: 'Untitled', 300),
            platform: $this->platform($raw, $url),
            uploader: $this->stringOrNull($raw['uploader'] ?? $raw['channel'] ?? $raw['creator'] ?? null),
            duration: $duration,
            thumbnail: $this->safeHttpUrl($raw['thumbnail'] ?? null),
            description: ($d = $this->stringOrNull($raw['description'] ?? null)) ? Str::limit($d, 1000) : null,
            options: [
                ...$this->videoOptions($formats, $duration),
                ...$this->audioOptions($formats, $duration),
            ],
        );
    }

    // ── Images (from gallery-dl) ───────────────────────────────────────────────

    /**
     * Adds image options to a media item, or builds an images-only item when yt-dlp found
     * nothing (e.g. a TikTok photo post). Returns $info unchanged when there are no images.
     *
     * @param array{meta: array, images: array} $gallery result of GalleryDl::images()
     */
    public function withImages(?MediaInfo $info, string $url, array $gallery): ?MediaInfo
    {
        $images = $gallery['images'] ?? [];
        if ($images === []) {
            return $info;
        }

        $options = [];

        if (count($images) > 1) {
            $options[] = new MediaFormatOption(
                key: 'images-all',
                type: MediaFormatOption::TYPE_IMAGE,
                label: 'All images (' . count($images) . ')',
                extension: 'zip',
                selector: '',
                preview: $images[0]['url'],
                positions: array_column($images, 'position'),
            );
        }

        foreach (array_values($images) as $i => $image) {
            $options[] = new MediaFormatOption(
                key: 'image-' . ($i + 1),
                type: MediaFormatOption::TYPE_IMAGE,
                label: 'Image ' . ($i + 1),
                extension: $image['extension'],
                selector: '',
                resolution: $image['width'] && $image['height'] ? "{$image['width']}x{$image['height']}" : null,
                preview: $image['url'],
                positions: [$image['position']],
            );
        }

        if ($info !== null) {
            return new MediaInfo(
                url: $info->url,
                title: $info->title,
                platform: $info->platform,
                uploader: $info->uploader,
                duration: $info->duration,
                thumbnail: $info->thumbnail ?? $images[0]['url'],
                description: $info->description,
                options: [...$info->options, ...$options],
            );
        }

        $meta = $gallery['meta'] ?? [];
        $text = $this->firstString($meta, ['title', 'desc', 'description', 'content', 'caption']);

        return new MediaInfo(
            url: $url,
            title: $text !== null ? Str::limit(preg_replace('/\s+/u', ' ', $text), 300) : 'Images',
            platform: $this->platform(['extractor_key' => (string) ($meta['category'] ?? '')], $url),
            uploader: $this->firstString($meta, ['uploader', 'username', 'nickname', 'fullname'])
                ?? $this->firstString((array) ($meta['author'] ?? []), ['nickname', 'name', 'uniqueId', 'username'])
                ?? $this->firstString((array) ($meta['user'] ?? []), ['name', 'username', 'nick']),
            duration: null,
            thumbnail: $images[0]['url'],
            description: $text !== null && mb_strlen($text) > 300 ? Str::limit($text, 1000) : null,
            options: $options,
        );
    }

    private function firstString(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (is_string($data[$key] ?? null) && trim($data[$key]) !== '') {
                return trim($data[$key]);
            }
        }

        return null;
    }

    // ── Video ──────────────────────────────────────────────────────────────────

    /** @return MediaFormatOption[] */
    private function videoOptions(array $formats, ?int $duration): array
    {
        $videos = array_values(array_filter($formats, fn (array $f) => $this->isVideo($f)));
        if ($videos === []) {
            return [];
        }

        $audios   = array_values(array_filter($formats, fn (array $f) => $this->isAudioOnly($f)));
        $options  = [];

        $best = $this->pickBestVideo($videos);
        $options[] = $this->videoOption('video-best', 'Best (' . $this->qualityLabel($best) . ')', $best, $audios, $duration, null);

        foreach (self::VIDEO_HEIGHTS as $i => $height) {
            $upper    = self::VIDEO_HEIGHTS[$i - 1] ?? PHP_INT_MAX;
            $inBucket = array_filter($videos, function (array $f) use ($height, $upper) {
                $short = $this->shortSide($f);

                return $short !== null
                    && $short >= $height * self::BUCKET_TOLERANCE
                    && $short < $upper * self::BUCKET_TOLERANCE;
            });

            if ($inBucket === []) {
                continue;
            }

            // Separate high-frame-rate (50/60 fps) and standard variants when both exist
            $high     = array_filter($inBucket, fn (array $f) => $this->fps($f) >= self::HIGH_FPS);
            $standard = array_diff_key($inBucket, $high);

            if ($high !== []) {
                $video     = $this->pickBestVideo($high);
                $options[] = $this->videoOption(
                    "video-{$height}-60",
                    $this->heightLabel($height, (int) round($this->fps($video))),
                    $video, $audios, $duration, $height, '[fps>30]',
                );
            }

            if ($standard !== []) {
                $options[] = $this->videoOption(
                    "video-{$height}",
                    $this->heightLabel($height),
                    $this->pickBestVideo($standard), $audios, $duration, $height,
                    $high !== [] ? '[fps<=?30]' : '',
                );
            }
        }

        return $options;
    }

    private function videoOption(
        string $key,
        string $label,
        array $video,
        array $audios,
        ?int $duration,
        ?int $maxHeight,
        string $fpsFilter = '',
    ): MediaFormatOption {
        $ext       = strtolower($video['ext'] ?? 'mp4');
        $id        = $this->safeFormatId($video);
        $dimension = $this->isPortrait($video) ? 'width' : 'height';
        $limit     = ($maxHeight !== null ? "[{$dimension}<=?{$maxHeight}]" : '') . $fpsFilter;
        $hasAudio  = $this->hasAudio($video);
        $size      = $this->size($video, $duration);

        if ($hasAudio || $audios === []) {
            $selector = implode('/', array_unique(array_filter([$id, "best{$limit}", 'best'])));

            return new MediaFormatOption(
                key: $key,
                type: MediaFormatOption::TYPE_VIDEO,
                label: $label . ($hasAudio ? '' : ' (no audio)'),
                extension: $ext,
                selector: $selector,
                resolution: $this->resolution($video),
                fps: $this->fps($video),
                videoCodec: $this->codec($video['vcodec'] ?? null),
                audioCodec: $this->codec($video['acodec'] ?? null),
                filesize: $size['bytes'],
                filesizeApproximate: $size['approx'],
            );
        }

        // Separate video + audio streams → FFmpeg merge. Match containers so no re-encode is needed.
        [$audioSelector, $mergeFormat, $audio] = $this->matchingAudio($ext, $audios);
        $audioSize = $this->size($audio, $duration);

        $selector = implode('/', array_unique(array_filter([
            $id ? "{$id}+{$audioSelector}" : null,
            "bestvideo{$limit}+bestaudio",
            "best{$limit}",
            'best',
        ])));

        return new MediaFormatOption(
            key: $key,
            type: MediaFormatOption::TYPE_VIDEO,
            label: $label,
            extension: $mergeFormat,
            selector: $selector,
            resolution: $this->resolution($video),
            fps: $this->fps($video),
            videoCodec: $this->codec($video['vcodec'] ?? null),
            audioCodec: $this->codec($audio['acodec'] ?? null),
            filesize: $size['bytes'] !== null ? $size['bytes'] + ($audioSize['bytes'] ?? 0) : null,
            filesizeApproximate: $size['approx'] || $audioSize['approx'],
            mergeOutputFormat: $mergeFormat,
            requiresFfmpeg: true,
        );
    }

    /** @return array{0: string, 1: string, 2: array} [audio selector, merge container, audio format] */
    private function matchingAudio(string $videoExt, array $audios): array
    {
        $m4a  = $this->pickBestAudio(array_filter($audios, fn ($a) => ($a['ext'] ?? '') === 'm4a'));
        $webm = $this->pickBestAudio(array_filter($audios, fn ($a) => ($a['ext'] ?? '') === 'webm'));

        if ($videoExt === 'mp4' && $m4a) {
            return ['bestaudio[ext=m4a]', 'mp4', $m4a];
        }

        if ($videoExt === 'webm' && $webm) {
            return ['bestaudio[ext=webm]', 'webm', $webm];
        }

        return ['bestaudio', 'mkv', $this->pickBestAudio($audios)];
    }

    // ── Audio ──────────────────────────────────────────────────────────────────

    /** @return MediaFormatOption[] */
    private function audioOptions(array $formats, ?int $duration): array
    {
        $audioOnly = array_values(array_filter($formats, fn (array $f) => $this->isAudioOnly($f)));
        $withAudio = array_values(array_filter($formats, fn (array $f) => $this->hasAudio($f)));

        if ($withAudio === []) {
            return [];
        }

        $options = [];

        if ($audioOnly !== []) {
            $best = $this->pickBestAudio($audioOnly);
            $size = $this->size($best, $duration);
            $id   = $this->safeFormatId($best);

            $options[] = new MediaFormatOption(
                key: 'audio-best',
                type: MediaFormatOption::TYPE_AUDIO,
                label: 'Best',
                extension: strtolower($best['ext'] ?? 'm4a'),
                selector: implode('/', array_filter([$id, 'bestaudio'])),
                audioCodec: $this->codec($best['acodec'] ?? null),
                filesize: $size['bytes'],
                filesizeApproximate: $size['approx'],
            );

            $m4a = $this->pickBestAudio(array_filter($audioOnly, fn ($a) => ($a['ext'] ?? '') === 'm4a'));
            if ($m4a && ($best['ext'] ?? '') !== 'm4a') {
                $size = $this->size($m4a, $duration);
                $id   = $this->safeFormatId($m4a);

                $options[] = new MediaFormatOption(
                    key: 'audio-m4a',
                    type: MediaFormatOption::TYPE_AUDIO,
                    label: 'M4A',
                    extension: 'm4a',
                    selector: implode('/', array_filter([$id, 'bestaudio[ext=m4a]'])),
                    audioCodec: $this->codec($m4a['acodec'] ?? null),
                    filesize: $size['bytes'],
                    filesizeApproximate: $size['approx'],
                );
            }
        } else {
            // Only muxed formats (common on TikTok / X): extract the audio track with FFmpeg
            $best  = $this->pickBestAudio($withAudio);
            $codec = $this->codec($best['acodec'] ?? null);

            $options[] = new MediaFormatOption(
                key: 'audio-best',
                type: MediaFormatOption::TYPE_AUDIO,
                label: 'Best',
                extension: $this->audioExtensionForCodec($codec),
                selector: 'bestaudio/best',
                audioCodec: $codec,
                filesize: null,
                audioConvertTo: 'best',
                requiresFfmpeg: true,
            );
        }

        $options[] = new MediaFormatOption(
            key: 'audio-mp3',
            type: MediaFormatOption::TYPE_AUDIO,
            label: 'MP3',
            extension: 'mp3',
            selector: 'bestaudio/best',
            audioCodec: 'mp3',
            filesize: $duration ? (int) ($duration * self::MP3_BITRATE_KBPS * 1000 / 8) : null,
            filesizeApproximate: true,
            audioConvertTo: 'mp3',
            requiresFfmpeg: true,
        );

        return $options;
    }

    // ── Format inspection helpers ──────────────────────────────────────────────

    private function usableFormats(array $raw): array
    {
        $formats = $raw['formats'] ?? null;

        // Single-format extractors put the format fields on the root object
        if (! is_array($formats) || $formats === []) {
            $formats = isset($raw['format_id']) || isset($raw['ext']) ? [$raw] : [];
        }

        return array_values(array_filter($formats, function ($f) {
            if (! is_array($f)) {
                return false;
            }
            $ext      = strtolower((string) ($f['ext'] ?? ''));
            $protocol = strtolower((string) ($f['protocol'] ?? ''));

            // Storyboards / image tracks are not media; plain image files are handled by gallery-dl
            return $ext !== 'mhtml' && $protocol !== 'mhtml'
                && ! in_array($ext, GalleryDl::IMAGE_EXTENSIONS, true)
                && ! str_starts_with((string) ($f['format_note'] ?? ''), 'storyboard');
        }));
    }

    private function isVideo(array $f): bool
    {
        $vcodec = $f['vcodec'] ?? null;

        if ($vcodec === 'none') {
            return false;
        }

        if ($vcodec !== null || isset($f['height']) || isset($f['width'])) {
            return true;
        }

        // Single-file extractors (Telegram posts…) report no codecs or size: go by the container
        return ! isset($f['acodec']) && in_array(strtolower((string) ($f['ext'] ?? '')), ['mp4', 'webm', 'mkv', 'mov', 'm4v', 'flv', '3gp'], true);
    }

    private function isAudioOnly(array $f): bool
    {
        return ($f['vcodec'] ?? null) === 'none' && ($f['acodec'] ?? 'none') !== 'none';
    }

    private function hasAudio(array $f): bool
    {
        $acodec = $f['acodec'] ?? null;

        // Unknown acodec on a muxed format usually means "has audio" (e.g. single-file extractors)
        return $acodec !== 'none' && ($acodec !== null || ! isset($f['vcodec']) || $f['vcodec'] !== 'none');
    }

    private function shortSide(array $f): ?int
    {
        $w = isset($f['width']) && is_numeric($f['width']) ? (int) $f['width'] : null;
        $h = isset($f['height']) && is_numeric($f['height']) ? (int) $f['height'] : null;

        if ($w && $h) {
            return min($w, $h);
        }

        return $h ?: $w;
    }

    private function isPortrait(array $f): bool
    {
        return isset($f['width'], $f['height']) && (int) $f['height'] > (int) $f['width'];
    }

    private function pickBestVideo(array $videos): array
    {
        usort($videos, fn (array $a, array $b) => [
            $this->shortSide($b) ?? 0,
            ($b['ext'] ?? '') === 'mp4' ? 1 : 0,
            (float) ($b['fps'] ?? 0),
            (float) ($b['tbr'] ?? $b['vbr'] ?? 0),
        ] <=> [
            $this->shortSide($a) ?? 0,
            ($a['ext'] ?? '') === 'mp4' ? 1 : 0,
            (float) ($a['fps'] ?? 0),
            (float) ($a['tbr'] ?? $a['vbr'] ?? 0),
        ]);

        return $videos[0];
    }

    private function pickBestAudio(array $audios): ?array
    {
        if ($audios === []) {
            return null;
        }

        usort($audios, fn (array $a, array $b) => (float) ($b['abr'] ?? $b['tbr'] ?? 0) <=> (float) ($a['abr'] ?? $a['tbr'] ?? 0));

        return array_values($audios)[0];
    }

    /** @return array{bytes: ?int, approx: bool} */
    private function size(?array $f, ?int $duration): array
    {
        if ($f === null) {
            return ['bytes' => null, 'approx' => true];
        }

        if (! empty($f['filesize'])) {
            return ['bytes' => (int) $f['filesize'], 'approx' => false];
        }

        if (! empty($f['filesize_approx'])) {
            return ['bytes' => (int) $f['filesize_approx'], 'approx' => true];
        }

        if ($duration && ! empty($f['tbr'])) {
            return ['bytes' => (int) ($f['tbr'] * 1000 / 8 * $duration), 'approx' => true];
        }

        return ['bytes' => null, 'approx' => true];
    }

    /** Only ids made of safe characters are pinned into a selector; others rely on the fallbacks. */
    private function safeFormatId(?array $f): ?string
    {
        $id = (string) ($f['format_id'] ?? '');

        return preg_match('/^[A-Za-z0-9_.\-]{1,64}$/', $id) ? $id : null;
    }

    private function qualityLabel(array $f): string
    {
        $short = $this->shortSide($f);
        $fps   = $this->fps($f);

        if (! $short) {
            return strtoupper($f['ext'] ?? 'video');
        }

        $bucket = null;
        foreach (self::VIDEO_HEIGHTS as $height) {
            if ($short >= $height * self::BUCKET_TOLERANCE) {
                $bucket = $height;
                break;
            }
        }

        $fpsSuffix = $fps >= self::HIGH_FPS ? (string) (int) round($fps) : '';

        return $bucket !== null && isset(self::RESOLUTION_NAMES[$bucket])
            ? self::RESOLUTION_NAMES[$bucket] . " {$short}p{$fpsSuffix}"
            : "{$short}p{$fpsSuffix}";
    }

    /** "1080p", "1080p60", "4K (2160p)", "2K (1440p60)" */
    private function heightLabel(int $height, ?int $fps = null): string
    {
        $base = "{$height}p" . ($fps ? (string) $fps : '');

        return isset(self::RESOLUTION_NAMES[$height]) ? self::RESOLUTION_NAMES[$height] . " ({$base})" : $base;
    }

    private function resolution(array $f): ?string
    {
        if (! empty($f['width']) && ! empty($f['height'])) {
            return "{$f['width']}x{$f['height']}";
        }

        return ! empty($f['height']) ? "{$f['height']}p" : null;
    }

    private function fps(array $f): ?float
    {
        return isset($f['fps']) && is_numeric($f['fps']) && $f['fps'] > 0 ? round((float) $f['fps'], 2) : null;
    }

    private function codec(?string $codec): ?string
    {
        if ($codec === null || $codec === 'none' || $codec === '') {
            return null;
        }

        // "avc1.64001F" → "avc1", "mp4a.40.2" → "mp4a"
        return Str::before($codec, '.');
    }

    private function audioExtensionForCodec(?string $codec): string
    {
        return match ($codec) {
            'opus'   => 'opus',
            'vorbis' => 'ogg',
            'mp3'    => 'mp3',
            'flac'   => 'flac',
            default  => 'm4a',
        };
    }

    public function platform(array $raw, string $url): string
    {
        $key = strtolower((string) ($raw['extractor_key'] ?? $raw['ie_key'] ?? ''));

        if (isset(self::PLATFORM_NAMES[$key])) {
            return self::PLATFORM_NAMES[$key];
        }

        foreach (self::PLATFORM_NAMES as $prefix => $name) {
            if ($key !== '' && str_starts_with($key, $prefix)) {
                return $name;
            }
        }

        $host = preg_replace('/^www\./', '', (string) parse_url($url, PHP_URL_HOST));

        return $key !== '' && $key !== 'generic' ? ($raw['extractor_key'] ?? $host) : ($host ?: 'Unknown');
    }

    private function safeHttpUrl(mixed $url): ?string
    {
        return is_string($url) && preg_match('#^https?://#i', $url) ? $url : null;
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
