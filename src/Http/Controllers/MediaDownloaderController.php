<?php

namespace ME\Utility\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use ME\Http\Controllers\Controller;
use ME\Utility\Models\MediaDownload;
use ME\Utility\Rules\PublicMediaUrl;
use ME\Utility\Services\MediaDownloader\CookieJar;
use ME\Utility\Services\MediaDownloader\Data\PlaylistInfo;
use ME\Utility\Services\MediaDownloader\Exceptions\MediaDownloaderException;
use ME\Utility\Services\MediaDownloader\MediaDownloadService;
use ME\Utility\Services\MediaDownloader\ServerCheck;
use ME\Utility\Services\MediaDownloader\ToolInstaller;
use ME\Utility\Services\MediaDownloader\YtDlp;
use ME\Utility\Services\MediaDownloader\UrlValidator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaDownloaderController extends Controller
{
    /** Option keys the UI can send; the service still checks the key exists for this media. */
    private const FORMAT_PATTERN = '/^(video-(best|\d{3,4}(-60)?)|audio-(best|m4a|mp3)|image-\d{1,4}|images-all)$/';

    public function __construct(
        private readonly MediaDownloadService $service,
        private readonly UrlValidator $urlValidator,
        private readonly CookieJar $cookies,
    ) {
    }

    // ── Page ───────────────────────────────────────────────────────────────────

    public function index()
    {
        $this->service->cleanupIfDue();

        $downloads = MediaDownload::ownedBy(auth()->id())
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $cookies = $this->cookies->summary(auth()->id());

        // Resume the progress panel of a bulk download that is still running
        $activeBatch = MediaDownload::ownedBy(auth()->id())
            ->whereNotNull('batch_id')
            ->whereIn('status', MediaDownload::ACTIVE_STATUSES)
            ->latest('id')
            ->value('batch_id');

        $presets  = MediaDownloadService::PRESETS;
        $maxBatch = (int) config('utility.media_downloader.max_batch_items', 200);

        return view('utility::media-downloader.index', compact('downloads', 'cookies', 'activeBatch', 'presets', 'maxBatch'));
    }

    // ── Analyze ────────────────────────────────────────────────────────────────

    public function analyze(Request $request): JsonResponse
    {
        $this->addMissingScheme($request);
        $data = $request->validate([
            'url' => ['required', 'string', 'max:2048', new PublicMediaUrl($this->urlValidator)],
        ]);

        try {
            $info = $this->service->inspect($data['url'], false, auth()->id());
        } catch (MediaDownloaderException $e) {
            return $this->error($e);
        }

        return response()->json([
            'kind' => $info instanceof PlaylistInfo ? 'playlist' : 'media',
            'data' => $info->toPublicArray(),
        ]);
    }

    // ── Download ───────────────────────────────────────────────────────────────

    public function download(Request $request): JsonResponse
    {
        $this->addMissingScheme($request);
        $data = $request->validate([
            'url'    => ['required', 'string', 'max:2048', new PublicMediaUrl($this->urlValidator)],
            'format' => ['required', 'string', 'regex:' . self::FORMAT_PATTERN],
        ]);

        try {
            $download = $this->service->start($data['url'], $data['format'], auth()->id());
        } catch (MediaDownloaderException $e) {
            return $this->error($e);
        }

        return $this->earlyJson([
            'data'       => $download->toStatusArray(),
            'status_url' => route('ut.media-downloader.status', $download->uuid),
            'cancel_url' => route('ut.media-downloader.cancel', $download->uuid),
        ], 202);
    }

    /**
     * Without a queue the job runs after this response is sent. A fixed length plus
     * "Connection: close" lets the browser finish reading it (and start polling progress)
     * even on servers without fastcgi_finish_request, e.g. `artisan serve`.
     */
    private function earlyJson(array $payload, int $status): JsonResponse
    {
        $response = response()->json($payload, $status);

        return $response->withHeaders([
            'Content-Length' => (string) strlen($response->getContent()),
            'Connection'     => 'close',
        ]);
    }

    // ── Bulk ───────────────────────────────────────────────────────────────────

    public function startBatch(Request $request): JsonResponse
    {
        $this->addMissingScheme($request);
        $max  = (int) config('utility.media_downloader.max_batch_items', 200);
        $data = $request->validate([
            'url'       => ['required', 'string', 'max:2048', new PublicMediaUrl($this->urlValidator)],
            'entries'   => ['required', 'array', 'min:1', "max:{$max}"],
            'entries.*' => ['string', 'regex:/^\d{1,5}$/'],
            'format'    => ['required', 'string', 'in:' . implode(',', MediaDownloadService::PRESETS)],
        ]);

        try {
            $batchId = $this->service->startBatch($data['url'], $data['entries'], $data['format'], auth()->id());
        } catch (MediaDownloaderException $e) {
            return $this->error($e);
        }

        return $this->earlyJson([
            'batch_id'   => $batchId,
            'status_url' => route('ut.media-downloader.batch.status', $batchId),
            'cancel_url' => route('ut.media-downloader.batch.cancel', $batchId),
        ], 202);
    }

    public function batchStatus(string $batch): JsonResponse
    {
        return response()->json(['data' => $this->service->batchStatus($batch, auth()->id())]);
    }

    public function batchCancel(string $batch): JsonResponse
    {
        $this->service->cancelBatch($batch, auth()->id());

        return response()->json(['data' => $this->service->batchStatus($batch, auth()->id())]);
    }

    public function batchRetry(string $batch): JsonResponse
    {
        $this->service->retryBatch($batch, auth()->id());

        return $this->earlyJson(['data' => $this->service->batchStatus($batch, auth()->id())], 200);
    }

    public function batchZip(string $batch): StreamedResponse
    {
        return $this->service->streamBatchZip($batch, auth()->id());
    }

    public function status(string $uuid): JsonResponse
    {
        return response()->json(['data' => $this->findOwned($uuid)->toStatusArray()]);
    }

    public function file(string $uuid): StreamedResponse
    {
        return $this->service->streamFile($this->findOwned($uuid));
    }

    public function cancel(string $uuid): JsonResponse
    {
        $download = $this->findOwned($uuid);
        $this->service->cancel($download);

        return response()->json(['data' => $download->fresh()->toStatusArray()]);
    }

    public function destroy(string $uuid): RedirectResponse
    {
        $this->service->forget($this->findOwned($uuid));

        return back()->with('success', 'Download removed from history.');
    }

    /** Clears the whole history; running downloads are cancelled and every stored file is removed. */
    public function destroyAll(): RedirectResponse
    {
        $count = 0;
        MediaDownload::ownedBy(auth()->id())->lazyById()->each(function (MediaDownload $download) use (&$count) {
            $this->service->forget($download);
            $count++;
        });

        return back()->with('success', $count . ' download(s) removed from history.');
    }

    // ── Server setup (shared hosting: install the tools without a terminal) ───

    public function setup(ToolInstaller $installer): JsonResponse
    {
        return response()->json($this->setupState($installer));
    }

    public function setupInstall(ToolInstaller $installer): JsonResponse
    {
        if ($reason = $this->cannotInstall($installer)) {
            return response()->json(['message' => $reason] + $this->setupState($installer), 422);
        }

        // yt-dlp is always refreshed (sites change often); FFmpeg only when none works yet
        $tools = [ToolInstaller::YT_DLP];
        if (app(YtDlp::class)->ffmpegVersion() === null) {
            $tools[] = ToolInstaller::FFMPEG;
        }

        $results = [];
        foreach ($tools as $tool) {
            try {
                $results[] = ['tool' => $tool, 'ok' => true, 'message' => $installer->install($tool)];
            } catch (\RuntimeException $e) {
                $results[] = ['tool' => $tool, 'ok' => false, 'message' => $e->getMessage()];
            }
        }

        // Re-resolve the binaries so the checks below see the fresh install
        foreach ([YtDlp::class, MediaDownloadService::class] as $abstract) {
            app()->forgetInstance($abstract);
        }

        return response()->json(['results' => $results] + $this->setupState($installer));
    }

    private function setupState(ToolInstaller $installer): array
    {
        return [
            'checks'        => app(ServerCheck::class)->run(),
            'can_install'   => $this->cannotInstall($installer) === null,
            'install_note'  => $this->cannotInstall($installer),
            'tools_dir'     => $installer->directory(),
        ];
    }

    private function cannotInstall(ToolInstaller $installer): ?string
    {
        if (! ServerCheck::functionEnabled('proc_open')) {
            return 'The host has disabled proc_open, so no program can run. Enable it first (see the list).';
        }

        return $installer->supported();
    }

    // ── Login cookies (own account, for private media) ───────────────────────

    public function storeCookies(Request $request): RedirectResponse
    {
        $request->validate([
            'cookies' => ['required', 'file', 'max:' . (CookieJar::MAX_BYTES / 1024)],
        ]);

        try {
            $domains = $this->cookies->put((int) auth()->id(), (string) file_get_contents($request->file('cookies')->getRealPath()));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['cookies' => $e->getMessage()]);
        }

        return back()->with('success', 'Cookies saved for: ' . implode(', ', array_slice($domains, 0, 8)) . (count($domains) > 8 ? '…' : ''));
    }

    public function destroyCookies(): RedirectResponse
    {
        $this->cookies->forget((int) auth()->id());

        return back()->with('success', 'Saved cookies deleted.');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * "tiktok.com/@user/photo/1" → "https://tiktok.com/@user/photo/1". Only a bare host name
     * gets a scheme; anything with another scheme (ftp://, file:…) is left for validation to reject.
     */
    private function addMissingScheme(Request $request): void
    {
        $url = trim((string) $request->input('url', ''));

        if ($url !== '' && ! preg_match('#^[a-z][a-z0-9+.\-]*:#i', $url) && preg_match('#^(//)?[\w\-]+(\.[\w\-]+)+(:\d+)?([/?\#]|$)#u', $url)) {
            $request->merge(['url' => 'https://' . ltrim($url, '/')]);
        }
    }

    private function findOwned(string $uuid): MediaDownload
    {
        return MediaDownload::ownedBy(auth()->id())->where('uuid', $uuid)->firstOrFail();
    }

    private function error(MediaDownloaderException $e): JsonResponse
    {
        $status = in_array($e->reason, [
            MediaDownloaderException::INVALID_URL,
            MediaDownloaderException::BLOCKED_URL,
            MediaDownloaderException::UNSUPPORTED_URL,
            MediaDownloaderException::UNSUPPORTED_FORMAT,
        ], true) ? 422 : 400;

        return response()->json(['message' => $e->userMessage(), 'reason' => $e->reason], $status);
    }
}
