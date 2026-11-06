<?php

namespace ME\Utility\Services\MediaDownloader;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ME\Utility\Jobs\MediaDownloadJob;
use ME\Utility\Models\MediaDownload;
use ME\Utility\Services\MediaDownloader\Data\MediaFormatOption;
use ME\Utility\Services\MediaDownloader\Data\MediaInfo;
use ME\Utility\Services\MediaDownloader\Data\PlaylistInfo;
use ME\Utility\Services\MediaDownloader\Exceptions\MediaDownloaderException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use ZipArchive;

class MediaDownloadService
{
    // Bump the version whenever the cached shape changes, so old analyses are not reused
    private const CACHE_PREFIX = 'ut-media-downloader:analysis:v4:';

    /** Bulk quality presets: "up to" a height, or audio. Resolved per item at download time. */
    public const PRESETS = ['best', '2160', '1440', '1080', '720', '480', '360', 'audio', 'mp3'];

    /** Queued (pending) items may wait this long behind others before being considered lost. */
    private const PENDING_STALE_HOURS = 24;

    /** Seconds between "was this cancelled?" DB checks while yt-dlp runs. */
    private const CANCEL_CHECK_INTERVAL = 1.0;

    public function __construct(
        private readonly UrlValidator $validator,
        private readonly YtDlp $ytDlp,
        private readonly FormatOptionBuilder $builder,
        private readonly PlaylistBuilder $playlists,
        private readonly GalleryDl $galleryDl,
        private readonly CookieJar $cookies,
        private readonly TelegramChannel $telegram,
        private readonly FacebookFallback $facebook,
        private readonly Cache $cache,
        private readonly array $config,
    ) {
    }

    // ── Analysis ───────────────────────────────────────────────────────────────

    /**
     * @param int|null $userId owner whose saved login cookies (if any) are used, so private
     *                         media that account can see is available
     */
    public function analyze(string $url, bool $fresh = false, ?int $userId = null): MediaInfo
    {
        $info = $this->inspect($url, $fresh, $userId);

        if ($info instanceof PlaylistInfo) {
            throw MediaDownloaderException::because(MediaDownloaderException::UNSUPPORTED_FORMAT, "{$url} is a list, not a single media item");
        }

        return $info;
    }

    /**
     * A single media item (MediaInfo) or a list of items (PlaylistInfo: playlist, channel,
     * profile, page with several videos).
     */
    public function inspect(string $url, bool $fresh = false, ?int $userId = null): MediaInfo|PlaylistInfo
    {
        $url = $this->validator->validate($url);
        // Results can differ per login, so never share a cache entry between users
        $key = self::CACHE_PREFIX . sha1($this->cookies->fingerprint($userId) . '|' . $url);

        if (! $fresh && is_array($cached = $this->cache->get($key)) && isset($cached['kind'], $cached['data'])) {
            return $cached['kind'] === 'playlist' ? PlaylistInfo::fromArray($cached['data']) : MediaInfo::fromArray($cached['data']);
        }

        try {
            $info = $this->cookies->withTemporaryFile(
                $userId,
                $this->temporaryRoot(),
                fn (?string $cookiesFile) => $this->extract($url, $cookiesFile),
            );
        } catch (MediaDownloaderException $e) {
            $e = $this->withLoginContext($e, $userId);
            $this->log('analyze failed', $e, ['url' => $url, 'with_cookies' => $this->cookies->has($userId)]);
            throw $e;
        }

        if ($info instanceof MediaInfo ? $info->options === [] : $info->entries === []) {
            throw MediaDownloaderException::because(MediaDownloaderException::UNSUPPORTED_FORMAT, "Nothing downloadable found at {$url}");
        }

        $this->cache->put($key, [
            'kind' => $info instanceof PlaylistInfo ? 'playlist' : 'media',
            'data' => $info->toArray(),
        ], now()->addMinutes((int) ($this->config['analysis_cache_minutes'] ?? 30)));

        return $info;
    }

    /**
     * yt-dlp for video / audio / lists; gallery-dl for images when yt-dlp found no video
     * (TikTok photo posts, image carousels…) or could not read the link at all.
     */
    private function extract(string $url, ?string $cookiesFile): MediaInfo|PlaylistInfo
    {
        // yt-dlp only knows single Telegram posts; whole public channels are listed from t.me/s/
        if (TelegramChannel::matches($url)) {
            return $this->telegram->list($url);
        }

        $raw     = null;
        $ytError = null;

        try {
            $raw = $this->ytDlp->metadata($url, $cookiesFile);
        } catch (MediaDownloaderException $e) {
            $ytError = $e;
        }

        // Facebook changes its page format often; the page yt-dlp fetched still holds the streams
        if ($raw === null && $ytError !== null && FacebookFallback::matches($url) && FacebookFallback::shouldTry($ytError)) {
            try {
                $raw = $this->facebook->info($url, $cookiesFile, $this->temporaryRoot() . '/fb-' . Str::random(16));
                $ytError = null;
            } catch (MediaDownloaderException $e) {
                $this->log('facebook fallback failed', $e, ['url' => $url]);
            }
        }

        if ($raw !== null && PlaylistBuilder::isPlaylist($raw)) {
            return $this->playlists->build($url, $raw, $this->ytDlp->maxPlaylistItems());
        }

        $media = $raw !== null ? $this->builder->build($url, $raw) : null;

        // Don't send a second request to a site that is already refusing this server
        $siteBlocking = $ytError?->reason === MediaDownloaderException::BLOCKED_BY_SITE;

        if (($media === null || $media->videoOptions() === []) && ! $siteBlocking && $this->galleryDl->available()) {
            try {
                $media = $this->builder->withImages($media, $url, $this->galleryDl->images($url, $cookiesFile));
            } catch (MediaDownloaderException $e) {
                $this->log('image lookup failed', $e, ['url' => $url]);
            }
        }

        return $media ?? throw ($ytError ?? MediaDownloaderException::because(MediaDownloaderException::EXTRACTION_FAILED));
    }

    // ── Download lifecycle ─────────────────────────────────────────────────────

    /**
     * Validates the request, records a pending download and dispatches the job.
     */
    public function start(string $url, string $formatKey, ?int $userId): MediaDownload
    {
        $info   = $this->analyze($url, false, $userId);
        $option = $this->optionOrFail($info, $formatKey);

        $download = MediaDownload::create([
            'user_id'     => $userId,
            'url'         => $info->url,
            'platform'    => $info->platform,
            'title'       => $info->title,
            'format'      => $option->key,
            'output_type' => $option->type,
            'extension'   => $option->extension,
            'filesize'    => $option->filesize,
            'status'      => MediaDownload::STATUS_PENDING,
            'stage'       => 'Queued',
        ]);

        $this->dispatch(new MediaDownloadJob($download->id, $info->url, $option->key, $option->type));

        return $download;
    }

    // ── Bulk (playlist / channel / profile) ────────────────────────────────────

    /**
     * Queues one download per selected entry of a list link. Entries are picked by id from the
     * server-side analysis, so the browser never supplies the URLs that get downloaded.
     *
     * @param string[] $entryIds
     * @return string batch id
     */
    public function startBatch(string $url, array $entryIds, string $preset, ?int $userId): string
    {
        if (! in_array($preset, self::PRESETS, true)) {
            throw MediaDownloaderException::because(MediaDownloaderException::UNSUPPORTED_FORMAT, "Unknown preset {$preset}");
        }

        $list = $this->inspect($url, false, $userId);
        if (! $list instanceof PlaylistInfo) {
            throw MediaDownloaderException::because(MediaDownloaderException::UNSUPPORTED_FORMAT, "{$url} is not a list");
        }

        $max     = $this->ytDlp->maxPlaylistItems();
        $entries = [];
        foreach (array_unique($entryIds) as $id) {
            $entry = $list->entry((string) $id);
            if ($entry !== null && $entry['kind'] === PlaylistInfo::KIND_VIDEO && $this->validator->isValid($entry['url'])) {
                $entries[] = $entry;
            }
        }

        if ($entries === [] || count($entries) > $max) {
            throw MediaDownloaderException::because(MediaDownloaderException::UNSUPPORTED_FORMAT, 'Selected ' . count($entries) . " items (max {$max})");
        }

        $batchId    = (string) Str::uuid();
        $outputType = in_array($preset, ['audio', 'mp3'], true) ? MediaFormatOption::TYPE_AUDIO : MediaFormatOption::TYPE_VIDEO;

        foreach ($entries as $entry) {
            $download = MediaDownload::create([
                'user_id'     => $userId,
                'batch_id'    => $batchId,
                'batch_title' => $list->title,
                'url'         => $entry['url'],
                'platform'    => $list->platform,
                'title'       => ($entry['untitled'] ?? false) ? null : $entry['title'],
                'thumbnail'   => $entry['thumbnail'],
                'format'      => 'preset-' . $preset,
                'output_type' => $outputType,
                'status'      => MediaDownload::STATUS_PENDING,
                'stage'       => 'Queued',
            ]);

            $this->dispatch(new MediaDownloadJob($download->id, $entry['url'], $download->format, $outputType));
        }

        return $batchId;
    }

    /** @return array{title: ?string, items: array, counts: array, done: bool, zip_url: ?string} */
    public function batchStatus(string $batchId, ?int $userId): array
    {
        $items = MediaDownload::ownedBy($userId)->where('batch_id', $batchId)->orderBy('id')->get();
        abort_if($items->isEmpty(), 404);

        $counts = [
            'total'     => $items->count(),
            'completed' => $items->where('status', MediaDownload::STATUS_COMPLETED)->count(),
            'failed'    => $items->where('status', MediaDownload::STATUS_FAILED)->count(),
            'cancelled' => $items->where('status', MediaDownload::STATUS_CANCELLED)->count(),
            'active'    => $items->filter->isActive()->count(),
        ];
        $withFiles = $items->filter->hasFile()->count();

        return [
            'title'   => $items->first()->batch_title,
            'items'   => $items->map->toStatusArray()->values()->all(),
            'counts'  => $counts,
            'done'    => $counts['active'] === 0,
            'zip_url' => $counts['active'] === 0 && $withFiles > 0 ? route('ut.media-downloader.batch.zip', $batchId) : null,
            'retry_url' => $counts['active'] === 0 && $counts['failed'] > 0 ? route('ut.media-downloader.batch.retry', $batchId) : null,
        ];
    }

    /** Re-queues the failed items of a bulk download (e.g. after a site's temporary block lifted). */
    public function retryBatch(string $batchId, ?int $userId): int
    {
        $failed = MediaDownload::ownedBy($userId)->where('batch_id', $batchId)
            ->where('status', MediaDownload::STATUS_FAILED)
            ->orderBy('id')
            ->get();

        foreach ($failed as $item) {
            $item->forceFill([
                'status'        => MediaDownload::STATUS_PENDING,
                'stage'         => 'Queued',
                'progress'      => null,
                'error_message' => null,
                'started_at'    => null,
                'completed_at'  => null,
            ])->save();

            $this->dispatch(new MediaDownloadJob($item->id, $item->url, $item->format, $item->output_type));
        }

        return $failed->count();
    }

    public function cancelBatch(string $batchId, ?int $userId): int
    {
        return MediaDownload::ownedBy($userId)->where('batch_id', $batchId)
            ->whereIn('status', MediaDownload::ACTIVE_STATUSES)
            ->get()
            ->filter(fn (MediaDownload $d) => $this->cancel($d))
            ->count();
    }

    /**
     * Streams every finished file of a batch as one ZIP (stored, not re-compressed: media is
     * already compressed). The archive is written while it is sent, so no temporary ZIP needs
     * disk space or time before the download starts; afterwards — unless keep_files — the
     * files are removed.
     */
    public function streamBatchZip(string $batchId, ?int $userId): StreamedResponse
    {
        // Ordered like the list, so images are numbered in post order
        $items = MediaDownload::ownedBy($userId)->where('batch_id', $batchId)->orderBy('id')->get()->filter->hasFile()->values();
        abort_if($items->isEmpty(), 404, 'No finished files in this bulk download.');

        $zip = new ZipStream();

        // Every image of every photo post goes into one "images/" folder, numbered serially in
        // post order (001.jpg, 002.jpg …); videos stay at the top level. Photo-post ZIPs are
        // read entry by entry, never unpacked.
        $imageTotal = $items->sum(fn (MediaDownload $d) => $this->imageCount($d->file_path));
        $width      = max(3, strlen((string) $imageTotal));
        $serial     = 0;
        $inners     = [];

        $used = [];
        foreach ($items as $item) {
            $path = $this->assertServableFile($item);
            $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            if ($ext === 'zip' || in_array($ext, GalleryDl::IMAGE_EXTENSIONS, true)) {
                $this->addImagesSerially($zip, $path, $inners, $serial, $width);
                continue;
            }

            $name = $item->file_name ?: basename($item->file_path);
            $base = pathinfo($name, PATHINFO_FILENAME);
            $next = pathinfo($name, PATHINFO_EXTENSION);
            for ($n = 2; isset($used[strtolower($name)]); $n++) {
                $name = "{$base}-{$n}.{$next}";
            }
            $used[strtolower($name)] = true;

            $zip->addFile($path, $name);
        }

        $keep     = (bool) ($this->config['keep_files'] ?? false);
        $fileName = (Str::limit(Str::slug((string) $items->first()->batch_title), 80, '') ?: 'media') . '.zip';

        return response()->streamDownload(function () use ($zip, $inners, $items, $keep) {
            @set_time_limit(0); // many large videos take longer than max_execution_time
            try {
                $zip->stream(function (string $bytes) {
                    echo $bytes;
                    flush();
                });
            } finally {
                foreach ($inners as $inner) {
                    $inner->close();
                }
                if (! $keep) {
                    foreach ($items as $item) {
                        $this->deleteDirectory($this->directoryFor($item));
                        $item->forgetFile();
                    }
                }
            }
        }, $fileName, [
            'Content-Type'           => 'application/zip',
            'Content-Length'         => (string) $zip->size(),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'no-store',
        ]);
    }

    /** Number of images a finished file holds: entries of a photo-post ZIP, 1 for an image, else 0. */
    private function imageCount(string $path): int
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($ext, GalleryDl::IMAGE_EXTENSIONS, true)) {
            return 1;
        }

        if ($ext !== 'zip') {
            return 0;
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return 0;
        }
        $count = $zip->numFiles;
        $zip->close();

        return $count;
    }

    /**
     * Adds an image (or every image inside a photo-post ZIP) as "images/<serial>.<ext>".
     * $serial continues across posts so the folder reads 001, 002, … in post order.
     * Opened photo-post ZIPs are collected in $inners and must stay open until streamed.
     *
     * @param list<ZipArchive> $inners
     */
    private function addImagesSerially(ZipStream $zip, string $path, array &$inners, int &$serial, int $width): void
    {
        $nameFor = fn (string $ext) => 'images/' . str_pad((string) ++$serial, $width, '0', STR_PAD_LEFT) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext !== 'zip') {
            $zip->addFile($path, $nameFor($ext));

            return;
        }

        $inner = new ZipArchive();
        if ($inner->open($path) !== true) {
            return;
        }
        $inners[] = $inner;

        for ($i = 0; $i < $inner->numFiles; $i++) {
            $stat     = $inner->statIndex($i) ?: ['name' => ''];
            $entryExt = strtolower(pathinfo((string) $stat['name'], PATHINFO_EXTENSION));
            if (! in_array($entryExt, GalleryDl::IMAGE_EXTENSIONS, true)) {
                continue;
            }

            // The entry name itself is never used as a path
            $zip->addStream($nameFor($entryExt), (int) $stat['size'], (int) $stat['crc'], fn () => $inner->getStreamIndex($i));
        }
    }

    /** Picks the option for a bulk preset: the best quality at or below the requested height. */
    public function resolvePreset(MediaInfo $info, string $preset): MediaFormatOption
    {
        $fail  = fn () => throw MediaDownloaderException::because(MediaDownloaderException::UNSUPPORTED_FORMAT, "No option for {$preset}");
        $name  = substr($preset, strlen('preset-'));
        $audio = $info->option('audio-best') ?? $info->option('audio-mp3');

        if ($name === 'mp3') {
            return $info->option('audio-mp3') ?? $fail();
        }

        if ($name === 'audio') {
            return $audio ?? $fail();
        }

        $videos = $info->videoOptions();
        if ($videos === []) {
            // Photo post in a profile (all its images), or an audio-only source such as a podcast
            return $info->option('images-all') ?? $info->option('image-1') ?? $audio ?? $fail();
        }

        if ($name === 'best') {
            return $info->option('video-best') ?? $videos[0];
        }

        // Height options are ordered high → low (60 fps first within a height)
        $sized = array_values(array_filter($videos, fn ($o) => preg_match('/^video-(\d+)(-60)?$/', $o->key)));
        foreach ($sized as $option) {
            if ((int) substr($option->key, 6) <= (int) $name) {
                return $option;
            }
        }

        // Everything is larger than requested → smallest available
        return end($sized) ?: $info->option('video-best') ?? $videos[0];
    }

    /**
     * Executes a download (called by MediaDownloadJob). Never throws for expected failures;
     * the outcome is recorded on the MediaDownload row.
     */
    public function run(MediaDownload $download, string $url, string $formatKey): void
    {
        $directory = $this->directoryFor($download);

        try {
            $this->pauseBetweenBulkItems($download);

            if (! $download->markAnalyzing()) {
                return; // cancelled (or already handled) before the worker picked it up
            }

            $url    = $this->validator->validate($url);
            $userId = $download->user_id !== null ? (int) $download->user_id : null;
            $info   = $this->analyze($url, false, $userId);
            $option = str_starts_with($formatKey, 'preset-')
                ? $this->resolvePreset($info, $formatKey)
                : $this->optionOrFail($info, $formatKey);

            if ($download->title === null || $download->extension === null || $download->thumbnail === null) {
                $download->forceFill([
                    'title'     => $download->title ?? $info->title,
                    'thumbnail' => $download->thumbnail ?? $info->thumbnail,
                    'extension' => $option->extension,
                    'filesize'  => $download->filesize ?? $option->filesize,
                ])->save();
            }

            $this->prepareDirectory($directory);

            if (! $download->markDownloading()) {
                return;
            }

            if ($option->isImage()) {
                $download->updateProgress(null, null, null, 'Downloading images');
                $files = $this->cookies->withTemporaryFile(
                    $userId,
                    $directory,
                    fn (?string $cookiesFile) => $this->galleryDl->download($url, $directory, $option->positions, $cookiesFile),
                );
                $this->packageImages($directory, $files);
            } else {
                $lastCheck = 0.0;
                $ytDownload = fn (?string $cookiesFile, ?string $infoJsonFile = null) => $this->ytDlp->download(
                    $url,
                    $option,
                    $directory,
                    onProgress: fn (?float $p, ?int $s, ?int $e, ?string $stage) => $download->updateProgress($p, $s, $e, $stage),
                    shouldCancel: function () use ($download, &$lastCheck): bool {
                        if (microtime(true) - $lastCheck < self::CANCEL_CHECK_INTERVAL) {
                            return false;
                        }
                        $lastCheck = microtime(true);

                        return MediaDownload::whereKey($download->id)->value('status') === MediaDownload::STATUS_CANCELLED;
                    },
                    cookiesFile: $cookiesFile,
                    infoJsonFile: $infoJsonFile,
                );

                $this->cookies->withTemporaryFile($userId, $directory, function (?string $cookiesFile) use ($ytDownload, $url, $directory) {
                    try {
                        $ytDownload($cookiesFile);
                    } catch (MediaDownloaderException $e) {
                        if (! FacebookFallback::matches($url) || ! FacebookFallback::shouldTry($e)) {
                            throw $e;
                        }
                        // Same streams the analysis found: download them from a prepared info dict
                        $infoJson = $directory . '/fallback.info.json';
                        file_put_contents($infoJson, json_encode($this->facebook->info($url, $cookiesFile, $directory . '/fb-pages')));
                        $ytDownload($cookiesFile, $infoJson);
                    }
                });
            }

            $file = $this->verifyOutput($directory);
            $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            $download->markCompleted($file, $this->safeFileName($download->title, $option, $ext), (int) filesize($file), $ext);
        } catch (MediaDownloaderException $e) {
            if ($e->reason === MediaDownloaderException::CANCELLED) {
                $download->markCancelled();
            } else {
                $this->log('download failed', $e, [
                    'download'     => $download->uuid,
                    'url'          => $url,
                    'format'       => $formatKey,
                    'with_cookies' => $this->cookies->has($download->user_id !== null ? (int) $download->user_id : null),
                ]);
                $download->markFailed($e->userMessage());

                if ($e->reason === MediaDownloaderException::BLOCKED_BY_SITE) {
                    $this->skipRestOfBatchIfBlocked($download);
                }
            }
        } catch (Throwable $e) {
            Log::error('[MediaDownloader] unexpected error', [
                'download' => $download->uuid,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);
            $download->markFailed(MediaDownloaderException::because(MediaDownloaderException::EXTRACTION_FAILED)->userMessage());
        } finally {
            // Only a completed download keeps its file (until fetched or expired)
            if (! $download->fresh()?->isCompleted()) {
                $this->deleteDirectory($directory);
            }
        }
    }

    public function cancel(MediaDownload $download): bool
    {
        if (! $download->markCancelled()) {
            return false;
        }

        // A running worker notices the status change and removes its own directory;
        // pending ones never created anything, but clean up defensively.
        if ($download->started_at === null) {
            $this->deleteDirectory($this->directoryFor($download));
        }

        return true;
    }

    /** Removes a history entry together with any file it still holds. */
    public function forget(MediaDownload $download): void
    {
        if ($download->isActive()) {
            $download->markCancelled();
        }

        $this->deleteDirectory($this->directoryFor($download));
        $download->delete();
    }

    /**
     * Streams the finished file to the browser, then removes it (unless keep_files is on).
     */
    public function streamFile(MediaDownload $download): StreamedResponse
    {
        $path = $this->assertServableFile($download);
        $keep = (bool) ($this->config['keep_files'] ?? false);
        $size = filesize($path);
        $dir  = $this->directoryFor($download);

        return response()->streamDownload(function () use ($path, $dir, $download, $keep) {
            try {
                $in = fopen($path, 'rb');
                while (! feof($in)) {
                    echo fread($in, 1024 * 1024);
                    flush();
                }
                fclose($in);
            } finally {
                if (! $keep) {
                    $this->deleteDirectory($dir);
                    $download->forgetFile();
                }
            }
        }, $download->file_name ?: basename($path), [
            'Content-Type'           => 'application/octet-stream',
            'Content-Length'         => (string) $size,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'no-store',
        ]);
    }

    /**
     * Fails downloads whose worker died and removes temp directories that are no longer needed.
     * Returns the number of directories removed.
     */
    public function cleanup(): int
    {
        $lifetime = (int) ($this->config['file_lifetime'] ?? 30);
        // Running downloads write progress every second or so; a row silent this long lost its worker
        $staleAfter = max((int) ($this->config['stall_timeout'] ?? 120) * 5, (int) ($this->config['analyze_timeout'] ?? 60) * 2, 900);

        // Rows stuck while being worked on (worker crashed / server restarted) → failed, so the UI
        // stops polling. Queued rows can legitimately wait behind a long bulk download.
        MediaDownload::query()
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->whereIn('status', [MediaDownload::STATUS_ANALYZING, MediaDownload::STATUS_DOWNLOADING])
                    ->where('updated_at', '<', now()->subSeconds($staleAfter)))
                ->orWhere(fn ($q) => $q->where('status', MediaDownload::STATUS_PENDING)
                    ->where('updated_at', '<', now()->subHours(self::PENDING_STALE_HOURS))))
            ->get()
            ->each(fn (MediaDownload $d) => $d->markFailed(MediaDownloaderException::because(MediaDownloaderException::TIMEOUT)->userMessage()));

        $root = $this->temporaryRoot();
        if (! is_dir($root)) {
            return 0;
        }

        $cutoff  = now()->subMinutes($lifetime)->getTimestamp();
        $removed = 0;

        foreach (File::directories($root) as $dir) {
            $uuid     = basename($dir);
            $download = Str::isUuid($uuid) ? MediaDownload::where('uuid', $uuid)->first() : null;

            $remove = match (true) {
                $download === null        => filemtime($dir) < $cutoff,           // unknown leftovers
                $download->isActive()     => false,                               // a worker is writing here
                $download->isCompleted()  => $lifetime > 0 && $this->finishedAt($download) < $cutoff,
                default                   => true,                                // failed / cancelled
            };

            if ($remove) {
                $this->deleteDirectory($dir);
                $download?->forgetFile();
                $removed++;
            }
        }

        // Leftover bulk ZIPs (a stream that was interrupted before its finally ran)
        foreach (glob($root . '/batch-*.zip') ?: [] as $zip) {
            if (filemtime($zip) < now()->subHour()->getTimestamp()) {
                @unlink($zip);
            }
        }

        return $removed;
    }

    /**
     * Hosts without cron (cPanel) never run the scheduler, so page visits and finished downloads
     * trigger the cleanup too, at most once every 10 minutes. A failure here never breaks them.
     */
    public function cleanupIfDue(): void
    {
        if (! $this->cache->add('media-downloader:cleanup', true, now()->addMinutes(10))) {
            return;
        }

        try {
            $this->cleanup();
        } catch (Throwable $e) {
            Log::warning('[MediaDownloader] cleanup failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * When a finished file starts to expire: for bulk items only once the whole batch is done,
     * so early items are still there for the ZIP. PHP_INT_MAX = batch still running.
     */
    private function finishedAt(MediaDownload $download): int
    {
        if ($download->batch_id === null) {
            return (int) $download->completed_at?->getTimestamp();
        }

        $batch = MediaDownload::where('batch_id', $download->batch_id);

        if ((clone $batch)->whereIn('status', MediaDownload::ACTIVE_STATUSES)->exists()) {
            return PHP_INT_MAX;
        }

        return (int) strtotime((string) (clone $batch)->max('completed_at'));
    }

    /**
     * "Log in required" is misleading when the user's login cookies were already used: then
     * yt-dlp simply cannot read the page (site change / extractor issue).
     */
    private function withLoginContext(MediaDownloaderException $e, ?int $userId): MediaDownloaderException
    {
        if ($e->reason === MediaDownloaderException::LOGIN_REQUIRED
            && $this->cookies->has($userId)
            && str_contains(strtolower((string) $e->technical), 'cannot parse data')) {
            return MediaDownloaderException::because(MediaDownloaderException::SITE_CHANGED, $e->technical, $e);
        }

        return $e;
    }

    /** Bulk items wait a few seconds after the previous one, so sites are not hit in a burst. */
    private function pauseBetweenBulkItems(MediaDownload $download): void
    {
        $delay = (int) ($this->config['bulk_delay'] ?? 0);

        if ($download->batch_id === null || $delay <= 0) {
            return;
        }

        $hasEarlierItem = MediaDownload::where('batch_id', $download->batch_id)
            ->where('id', '<', $download->id)
            ->where('status', '!=', MediaDownload::STATUS_PENDING)
            ->exists();

        if ($hasEarlierItem) {
            sleep($delay);
        }
    }

    /**
     * When a site refuses several items of a batch in a row, it is blocking this server for now:
     * the remaining items are skipped instead of being hammered (which prolongs such blocks).
     * They can be re-queued later with retryBatch().
     */
    private function skipRestOfBatchIfBlocked(MediaDownload $download): void
    {
        $threshold = (int) ($this->config['bulk_block_threshold'] ?? 0);

        if ($download->batch_id === null || $threshold <= 0) {
            return;
        }

        $blockedMessage = MediaDownloaderException::because(MediaDownloaderException::BLOCKED_BY_SITE)->userMessage();

        $recent = MediaDownload::where('batch_id', $download->batch_id)
            ->whereIn('status', [MediaDownload::STATUS_COMPLETED, MediaDownload::STATUS_FAILED])
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->limit($threshold)
            ->pluck('error_message');

        if ($recent->count() < $threshold || $recent->contains(fn ($m) => $m !== $blockedMessage)) {
            return;
        }

        MediaDownload::where('batch_id', $download->batch_id)
            ->where('status', MediaDownload::STATUS_PENDING)
            ->get()
            ->each(fn (MediaDownload $d) => $d->markFailed(
                "Skipped: the site refused the last {$threshold} items in a row, so the rest were not attempted.\n"
                . 'Wait a while and use Retry failed.'
            ));
    }

    private function dispatch(MediaDownloadJob $job): void
    {
        if ($this->config['queue']['enabled'] ?? false) {
            dispatch($job
                ->onConnection($this->config['queue']['connection'] ?? null)
                ->onQueue($this->config['queue']['name'] ?? 'default'));
        } else {
            // No worker required: runs in this PHP process once the response has been sent
            // (bulk items run one after another)
            dispatch($job)->afterResponse();
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function temporaryRoot(): string
    {
        return rtrim((string) ($this->config['temporary_directory'] ?? storage_path('app/media-temp')), '/');
    }

    public function directoryFor(MediaDownload $download): string
    {
        // uuid is generated server-side, never user input
        return $this->temporaryRoot() . '/' . $download->uuid;
    }

    private function optionOrFail(MediaInfo $info, string $formatKey): MediaFormatOption
    {
        return $info->option($formatKey)
            ?? throw MediaDownloaderException::because(MediaDownloaderException::UNSUPPORTED_FORMAT, "Unknown format key {$formatKey}");
    }

    private function prepareDirectory(string $directory): void
    {
        $root = $this->temporaryRoot();

        try {
            // Root is shared by the web user (e.g. www-data) and CLI / cron users, like Laravel's
            // storage dir, so it must stay group-writable; each download's own dir is not public.
            self::makeDirectory($root, 0775);
            self::makeDirectory($directory, 0770);
        } catch (Throwable $e) {
            throw MediaDownloaderException::because(MediaDownloaderException::PERMISSION_DENIED, $e->getMessage(), $e);
        }

        if (! is_writable($directory)) {
            throw MediaDownloaderException::because(MediaDownloaderException::PERMISSION_DENIED, "Not writable: {$directory}");
        }

        $free = @disk_free_space($root);
        $min  = (int) ($this->config['min_free_space'] ?? 0);
        if ($free !== false && $free < $min) {
            throw MediaDownloaderException::because(MediaDownloaderException::DISK_SPACE, "Free: {$free} bytes, required: {$min}");
        }
    }

    /** mkdir ignoring the process umask, so the intended mode really applies. */
    public static function makeDirectory(string $path, int $mode): void
    {
        if (is_dir($path)) {
            return;
        }

        File::ensureDirectoryExists($path, $mode);
        @chmod($path, $mode);
    }

    /**
     * One image → media.<ext>; several → media.zip (stored, images are already compressed).
     *
     * @param string[] $files
     */
    private function packageImages(string $directory, array $files): void
    {
        if (count($files) === 1) {
            $ext = strtolower(pathinfo($files[0], PATHINFO_EXTENSION)) ?: 'jpg';
            rename($files[0], $directory . '/media.' . ($ext === 'jpeg' ? 'jpg' : $ext));

            return;
        }

        $zip = new ZipArchive();
        if ($zip->open($directory . '/media.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw MediaDownloaderException::because(MediaDownloaderException::PERMISSION_DENIED, 'Cannot create image ZIP');
        }

        $width = strlen((string) count($files));
        foreach (array_values($files) as $i => $file) {
            $name = str_pad((string) ($i + 1), max(2, $width), '0', STR_PAD_LEFT) . '.' . strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $zip->addFile($file, $name);
            $zip->setCompressionName($name, ZipArchive::CM_STORE);
        }
        $zip->close();

        array_map('unlink', $files);
    }

    /** Returns the single finished media file in $directory. */
    private function verifyOutput(string $directory): string
    {
        $files = array_values(array_filter(
            glob($directory . '/media.*') ?: [],
            fn (string $f) => is_file($f) && ! preg_match('/\.(part|ytdl|temp|tmp)$|\.part-Frag|\.f\d+\./', $f)
        ));

        if ($files === []) {
            throw MediaDownloaderException::because(MediaDownloaderException::EXTRACTION_FAILED, 'yt-dlp finished without an output file');
        }

        usort($files, fn ($a, $b) => filesize($b) <=> filesize($a));
        $file = $files[0];
        $size = filesize($file);

        if ($size === false || $size === 0) {
            throw MediaDownloaderException::because(MediaDownloaderException::EXTRACTION_FAILED, 'Output file is empty');
        }

        if ($size > (int) ($this->config['max_file_size'] ?? PHP_INT_MAX)) {
            throw MediaDownloaderException::because(MediaDownloaderException::TOO_LARGE, "Output {$size} bytes");
        }

        return $file;
    }

    private function assertServableFile(MediaDownload $download): string
    {
        abort_unless($download->hasFile(), 404, 'This file is no longer available. Please download it again.');

        $root = realpath($this->temporaryRoot());
        $path = realpath($download->file_path);

        // Defence in depth: never serve anything outside the temp root
        abort_unless($root && $path && str_starts_with($path, $root . DIRECTORY_SEPARATOR), 404);

        return $path;
    }

    private function safeFileName(?string $title, MediaFormatOption $option, string $ext): string
    {
        $base   = Str::limit(Str::slug((string) $title), 80, '') ?: 'media';
        $suffix = Str::slug($option->label) ?: $option->type;
        $ext    = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'bin';

        return "{$base}-{$suffix}.{$ext}";
    }

    private function deleteDirectory(string $directory): void
    {
        $root = $this->temporaryRoot();

        if ($directory !== $root && str_starts_with($directory, $root . '/') && is_dir($directory)) {
            File::deleteDirectory($directory);
        }
    }

    private function log(string $message, MediaDownloaderException $e, array $context = []): void
    {
        Log::warning("[MediaDownloader] {$message}", $context + [
            'reason'    => $e->reason,
            'technical' => $e->technical,
        ]);
    }
}
