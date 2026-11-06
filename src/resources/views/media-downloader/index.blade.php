@extends('me::master')

@section('title', 'Media Downloader')

@php
    // Header button state: green = every known site logged in, yellow = some login missing/expired
    $cookieSessionsOk = $cookies && ! in_array(false, $cookies['sessions'] ?? [], true);
@endphp

@push('buttons')
    <button type="button" class="btn btn-sm btn-encodex-show" data-bs-toggle="modal" data-bs-target="#mdSetupModal"
            title="Check the server and install yt-dlp / FFmpeg">
        <i class="fas fa-tools me-1"></i> <span class="hide-mobile">Server setup</span>
    </button>
    <button type="button" class="btn btn-sm btn-encodex-edit" data-bs-toggle="modal" data-bs-target="#mdCookiesModal"
            title="Login cookies for private media">
        <i class="fas fa-key me-1"></i> <span class="hide-mobile">Login cookies</span>
        @if($cookies)
            <i class="fas {{ $cookieSessionsOk ? 'fa-check-circle text-success' : 'fa-exclamation-circle text-warning' }}"></i>
        @endif
    </button>
@endpush

@push('css')
<style>
    /* Divider between media info and the format tables, only when they sit side by side */
    @media (min-width: 992px) {
        #mdResults .md-formats { border-left: 1px solid var(--bs-border-color); padding-left: 1.5rem; }
    }

    /* Thumbnails are blurred; pressing and holding the centre button shows them clearly */
    .md-media { position: relative; overflow: hidden; user-select: none; -webkit-user-select: none; -webkit-touch-callout: none; }
    .md-media img { display: block; filter: blur(22px); transform: scale(1.15); transition: filter .12s ease, transform .12s ease; pointer-events: none; }
    .md-media.md-media-sm img { filter: blur(8px); }
    .md-media.md-reveal img { filter: none; transform: none; }
    .md-peek {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
        width: 46px; height: 46px; border: 0; border-radius: 50%;
        background: rgba(0, 0, 0, .5); color: #fff;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; touch-action: none; transition: opacity .12s ease;
    }
    .md-media-sm .md-peek { width: 24px; height: 24px; font-size: .65rem; }
    .md-img-tile { width: 120px; height: 120px; overflow: hidden; }
    .md-img-tile img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .md-media.md-reveal .md-peek { opacity: 0; }
</style>
@endpush

@section('content')

{{-- Flash / validation messages are shown as toasts by the me::master layout (toastr) --}}

{{-- ── URL form --}}
<div class="card glass-card p-4 mb-4">
    <div class="fw-semibold mb-3">
        <i class="fas fa-cloud-download-alt text-primary me-2"></i>Media Downloader
    </div>

    <form id="mdAnalyzeForm" class="no-loader ajax-form" autocomplete="off" novalidate>
        <label for="mdUrl" class="form-label small text-muted">Paste public media URL</label>
        <div class="input-group">
            <input type="url" id="mdUrl" name="url" class="form-control"
                   placeholder="https://example.com/..." maxlength="2048" required>
            <button type="submit" id="mdAnalyzeBtn" class="btn btn-encodex-search px-4">
                <i class="fas fa-search me-1"></i> Analyze
            </button>
            <button type="button" id="mdPasteAnalyzeBtn" class="btn btn-encodex-create px-3" title="Paste URL from clipboard and analyze">
                <i class="fas fa-paste me-1"></i> Paste &amp; Analyze
            </button>
        </div>
        <div class="form-text">
            YouTube, TikTok, Facebook, Instagram, X/Twitter, Pinterest and many more.
            Playlists, channels and profiles work too — pick the videos and download them in bulk.
            Paste another link any time (or several at once) — each one gets its own result card.
            For private media your account can see, add your login cookies with the <strong>Login cookies</strong> button above.
        </div>
    </form>

</div>

{{-- ── Analysis results: one card per analyzed URL (newest first), built from the template below --}}
<div id="mdResults"></div>

<template id="mdResultTpl">
<div class="card glass-card p-4 mb-4 md-result">
    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
        <a data-md="url" href="#" target="_blank" rel="noopener noreferrer" class="small text-muted text-break no-loader"></a>
        <button type="button" data-md="close" class="btn-close" title="Remove this result" aria-label="Remove this result"></button>
    </div>

    <div data-md="loading" class="d-flex align-items-center gap-2 text-muted small">
        <span class="spinner-border spinner-border-sm"></span> Analyzing…
    </div>
    <div data-md="error" class="alert alert-danger py-2 mb-0 d-none" style="white-space:pre-line;"></div>

    {{-- Single media: media info (left) · formats (right) --}}
    <div data-md="info" class="row g-4 d-none">
        <div class="col-lg-4">
            <div class="row g-3">
                <div class="col-sm-6">
                    <div data-md="thumbBox" class="md-media rounded border d-none">
                        <img data-md="thumb" src="" alt="Thumbnail" class="w-100"
                             style="max-height:260px;object-fit:cover;" referrerpolicy="no-referrer" loading="lazy">
                        <button type="button" class="md-peek" title="Press and hold to view" aria-label="Press and hold to view">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div data-md="thumbEmpty" class="rounded border d-flex align-items-center justify-content-center text-muted"
                         style="height:160px;"><i class="fas fa-film fa-2x opacity-25"></i></div>
                </div>
                <div class="col-sm-6">
                    <h5 data-md="title" class="fw-bold mb-3 text-break"></h5>
                    @foreach(['Platform' => 'platform', 'Duration' => 'duration', 'Uploader' => 'uploader'] as $label => $role)
                        <div class="d-flex gap-2 small mb-1">
                            <span class="text-muted text-nowrap" style="min-width:72px;">{{ $label }}</span>
                            <span data-md="{{ $role }}" class="text-break"></span>
                        </div>
                    @endforeach
                    <details data-md="descWrap" class="small mt-2 d-none">
                        <summary class="text-muted">Description</summary>
                        <p data-md="description" class="mt-2 mb-0 text-break" style="white-space:pre-line;"></p>
                    </details>
                </div>
            </div>
        </div>

        <div class="col-lg-8 md-formats">
            <div data-md="videoSection">
            <div class="fw-semibold mb-2"><i class="fas fa-video text-primary me-2"></i>Video</div>
            <div class="table-responsive mb-3">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr class="small text-muted">
                            <th>Quality</th><th>Format</th><th class="d-none d-md-table-cell">Resolution</th>
                            <th class="d-none d-md-table-cell">FPS</th><th class="d-none d-xl-table-cell">Codecs</th>
                            <th>Size</th><th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody data-md="videoRows"></tbody>
                </table>
            </div>
            </div>

            <div data-md="audioSection" class="mb-3">
            <div class="fw-semibold mb-2"><i class="fas fa-music text-success me-2"></i>Audio</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr class="small text-muted">
                            <th>Quality</th><th>Format</th><th class="d-none d-xl-table-cell">Codec</th>
                            <th>Size</th><th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody data-md="audioRows"></tbody>
                </table>
            </div>
            </div>

            {{-- Images of the post (gallery-dl): blurred previews, one download each, or all as ZIP --}}
            <div data-md="imageSection" class="d-none">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <div class="fw-semibold">
                        <i class="fas fa-images text-warning me-2"></i>Images <span data-md="imageCount" class="small text-muted"></span>
                    </div>
                    <button type="button" data-md="imagesAllBtn" class="btn btn-sm btn-encodex-create d-none">
                        <i class="fas fa-file-archive me-1"></i> Download all (ZIP)
                    </button>
                </div>
                <div data-md="imageGrid" class="d-flex flex-wrap gap-3"></div>
            </div>
        </div>
    </div>

    {{-- List result (playlist / channel / profile / multi-video page) --}}
    <div data-md="list" class="d-none">
        <div class="text-break mb-2">
            <h5 data-md="plTitle" class="fw-bold mb-1"></h5>
            <div class="small text-muted">
                <span data-md="plPlatform"></span>
                <span data-md="plUploader"></span>
                · <span data-md="plCount"></span>
            </div>
            <div data-md="plTruncated" class="small text-warning d-none">
                <i class="fas fa-info-circle me-1"></i>Only the first {{ $maxBatch }} items were loaded (MEDIA_DOWNLOADER_MAX_BATCH).
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <div class="form-check me-2">
                <label class="form-check-label small">
                    <input class="form-check-input" type="checkbox" data-md="plAll" checked> Select all
                </label>
            </div>
            <div data-md="plTypeFilter" class="btn-group btn-group-sm me-1 d-none" role="group" aria-label="Select by type">
                <button type="button" class="btn btn-outline-secondary" data-select="video"><i class="fas fa-film me-1"></i>Only videos</button>
                <button type="button" class="btn btn-outline-secondary" data-select="images"><i class="fas fa-images me-1"></i>Only photos</button>
            </div>
            <select data-md="plPreset" class="form-select form-select-sm" style="max-width:260px;">
                <option value="best">Best quality</option>
                <option value="2160">4K (2160p) or best below</option>
                <option value="1440">2K (1440p) or best below</option>
                <option value="1080" selected>1080p or best below</option>
                <option value="720">720p or best below</option>
                <option value="480">480p or best below</option>
                <option value="360">360p or best below</option>
                <option value="audio">Audio only (original)</option>
                <option value="mp3">Audio only (MP3)</option>
            </select>
            <button type="button" data-md="plStart" class="btn btn-sm btn-encodex-create">
                <i class="fas fa-download me-1"></i> Download selected (<span data-md="plSelected">0</span>)
            </button>
        </div>

        <div class="table-responsive" style="max-height:480px;overflow-y:auto;">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead>
                    <tr class="small text-muted">
                        <th></th><th></th><th>Title</th><th>Type</th><th>Duration</th><th></th>
                    </tr>
                </thead>
                <tbody data-md="plRows"></tbody>
            </table>
        </div>
    </div>

    {{-- Progress of a download started from this result is shown here --}}
    <div data-md="progressSlot"></div>
</div>
</template>

{{-- ── Bulk progress --}}
<div id="mdBatch" class="card glass-card p-4 mb-4 d-none">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
        <div class="text-break">
            <div class="fw-semibold" id="mdBatchStage">Bulk download…</div>
            <div class="small text-muted" id="mdBatchTitle"></div>
        </div>
        <div class="fs-5 fw-bold text-primary" id="mdBatchCount"></div>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
        <div class="progress flex-grow-1" style="height:8px;min-width:160px;">
            <div id="mdBatchBar" class="progress-bar" role="progressbar" style="width:0%"></div>
        </div>
        <div class="d-flex gap-2 ms-auto">
            <button type="button" id="mdBatchCancel" class="btn btn-sm btn-encodex-cancel text-nowrap">
                <i class="fas fa-times me-1"></i> Cancel remaining
            </button>
            <button type="button" id="mdBatchRetry" class="btn btn-sm btn-encodex-active text-nowrap d-none">
                <i class="fas fa-redo me-1"></i> Retry failed
            </button>
            <a id="mdBatchZip" href="#" class="btn btn-sm btn-encodex-save no-loader text-nowrap d-none" download>
                <i class="fas fa-file-archive me-1"></i> Download all as ZIP
            </a>
        </div>
    </div>
    <div class="small text-muted mb-3" id="mdBatchSummary"></div>
    <div id="mdBatchError" class="alert alert-danger py-2 d-none" style="white-space:pre-line;"></div>
    <div class="table-responsive" style="max-height:420px;overflow-y:auto;">
        <table class="table table-sm align-middle mb-0">
            <tbody id="mdBatchRows"></tbody>
        </table>
    </div>
</div>

{{-- ── Progress of history downloads (re-download / resume); result cards get their own copy --}}
<div id="mdProgressCard" class="card glass-card p-4 mb-4 d-none"></div>

<template id="mdProgressTpl">
    <div>
        <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-2">
            <div class="text-break">
                <span class="fw-semibold" data-md="stage">Downloading…</span>
                <span class="small text-muted ms-1" data-md="title"></span>
            </div>
            <div class="small text-muted text-nowrap">
                Speed <strong data-md="speed">–</strong>
                <span class="mx-1">·</span> ETA <strong data-md="eta">–</strong>
                <span class="mx-1">·</span> Size <strong data-md="size">–</strong>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="progress flex-grow-1" style="height:8px;min-width:120px;">
                <div data-md="bar" class="progress-bar" role="progressbar" style="width:0%"></div>
            </div>
            <div class="fw-bold text-primary text-nowrap text-end" data-md="percent" style="min-width:3.2rem;"></div>
            <button type="button" data-md="cancel" class="btn btn-sm btn-encodex-cancel text-nowrap">
                <i class="fas fa-times me-1"></i> Cancel
            </button>
            <a data-md="save" href="#" class="btn btn-sm btn-encodex-save no-loader text-nowrap d-none" download>
                <i class="fas fa-download me-1"></i> Save file
            </a>
        </div>
        <div data-md="error" class="alert alert-danger py-2 mt-2 mb-0 d-none" style="white-space:pre-line;"></div>
    </div>
</template>

{{-- ── History --}}
<div class="card glass-card">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div class="fw-semibold"><i class="fas fa-history text-warning me-2"></i>Recent Downloads</div>
            @if($downloads->isNotEmpty())
                <form method="POST" action="{{ route('ut.media-downloader.destroy-all') }}" class="d-inline"
                      onsubmit="if (!confirm('Delete the whole download history? Running downloads are cancelled and all saved files are removed.')) { event.stopPropagation(); return false; }">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-encodex-delete"><i class="fas fa-trash me-1"></i> Delete all</button>
                </form>
            @endif
        </div>

        @if($downloads->isEmpty())
            <div class="text-center py-4 text-muted">
                <i class="fas fa-cloud-download-alt fa-3x mb-3 opacity-25"></i>
                <p class="mb-0">No downloads yet.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-hover table-encodex   align-middle mb-0">
                    <thead>
                        <tr class="small text-muted">
                            <th>Title</th>
                            <th class="d-none d-md-table-cell">Platform</th>
                            <th>Format</th>
                            <th class="d-none d-md-table-cell">Size</th>
                            <th>Status</th>
                            <th class="d-none d-lg-table-cell">When</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($downloads as $download)
                            @php
                                $badge = [
                                    'pending'     => 'secondary',
                                    'analyzing'   => 'info',
                                    'downloading' => 'primary',
                                    'completed'   => 'success',
                                    'failed'      => 'danger',
                                    'cancelled'   => 'warning',
                                ][$download->status] ?? 'secondary';
                            @endphp
                            <tr>
                                <td class="small text-truncate" style="max-width:260px;" title="{{ $download->title }}">
                                    {{ $download->title ?? '—' }}
                                    @if($download->batch_title)
                                        <div class="text-muted text-truncate" style="font-size:.75rem;">
                                            <i class="fas fa-layer-group me-1"></i>{{ $download->batch_title }}
                                        </div>
                                    @endif
                                </td>
                                <td class="small d-none d-md-table-cell">{{ $download->platform ?? '—' }}</td>
                                <td class="small">
                                    {{ $download->format }}
                                    @if($download->extension)<span class="text-muted">· {{ strtoupper($download->extension) }}</span>@endif
                                </td>
                                <td class="small d-none d-md-table-cell">{{ $download->formatted_size ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}" @if($download->error_message) title="{{ $download->error_message }}" @endif>
                                        {{ ucfirst($download->status) }}
                                    </span>
                                </td>
                                <td class="small text-muted d-none d-lg-table-cell">{{ $download->created_at->diffForHumans() }}</td>
                                <td class="text-end text-nowrap">
                                    @if($download->hasFile())
                                        <a href="{{ route('ut.media-downloader.file', $download->uuid) }}" class="btn btn-sm btn-encodex-save no-loader" download>
                                            <i class="fas fa-download"></i>
                                        </a>
                                    @elseif($download->isActive())
                                        <button type="button" class="btn btn-sm btn-encodex-show"
                                                onclick="mdTrack(@js(route('ut.media-downloader.status', $download->uuid)), @js(route('ut.media-downloader.cancel', $download->uuid)))">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    @endif
                                    @unless($download->isActive())
                                        <button type="button" class="btn btn-sm btn-encodex-print" title="Re-download"
                                                data-url="{{ $download->url }}" data-format="{{ $download->format }}" data-title="{{ $download->title }}"
                                                onclick="mdRedownload(this)">
                                            <i class="fas fa-redo"></i>
                                        </button>
                                    @endunless
                                    {{-- target=_blank: opens in a new tab and is skipped by the theme's page loader --}}
                                    <a href="{{ $download->url }}" target="_blank" rel="noopener noreferrer"
                                       class="btn btn-sm btn-encodex-show no-loader" title="Open original link">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <form method="POST" action="{{ route('ut.media-downloader.destroy', $download->uuid) }}" class="d-inline"
                                          onsubmit="if (!confirm('Remove this download from history?')) { event.stopPropagation(); return false; }">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-encodex-delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="">
                {{ $downloads->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

{{-- ── Server setup modal: checks + one-click install of yt-dlp / FFmpeg (shared hosting) --}}
<div class="modal fade" id="mdSetupModal" tabindex="-1" aria-labelledby="mdSetupModalLabel">
    <div class="modal-dialog modal-lg glass-card">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mdSetupModalLabel">
                    <i class="fas fa-tools text-primary me-2"></i>Server setup
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">
                    The downloader needs <strong>yt-dlp</strong> and <strong>FFmpeg</strong>. If the server has none
                    (cPanel / shared hosting), click <strong>Install / update tools</strong>: they are downloaded
                    into this app's storage and used automatically — no terminal, no .env change.
                    Click it again now and then to update yt-dlp when sites stop working.
                </p>
                <div id="mdSetupLoading" class="text-muted small"><span class="spinner-border spinner-border-sm me-1"></span> Checking the server…</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <tbody id="mdSetupRows"></tbody>
                    </table>
                </div>
                <div id="mdSetupNote" class="alert alert-warning py-2 mt-3 mb-0 small d-none"></div>
                <div id="mdSetupResults" class="mt-3 small"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-encodex-cancel btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" id="mdSetupInstall" class="btn btn-sm btn-encodex-save" disabled>
                    <span class="md-btn-label"><i class="fas fa-download me-1"></i> Install / update tools</span>
                    <span class="md-btn-busy d-none"><span class="spinner-border spinner-border-sm me-1"></span> Installing… (1–2 min)</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Login cookies modal (own account → private media) --}}
<div class="modal fade" id="mdCookiesModal" tabindex="-1" aria-labelledby="mdCookiesModalLabel">
    <div class="modal-dialog modal-lg modal-dialog-scrollablex glass-card">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mdCookiesModalLabel">
                    <i class="fas fa-key text-warning me-2"></i>Login cookies
                    @if($cookies)
                        <span class="badge bg-success-subtle text-success ms-1">Saved</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary ms-1">Not set</span>
                    @endif
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                @if($cookies)
                    <div class="small text-muted">
                        Used for: {{ implode(', ', array_slice($cookies['domains'], 0, 10)) }}{{ count($cookies['domains']) > 10 ? '…' : '' }}
                        @if($cookies['updated_at']) · updated {{ $cookies['updated_at']->diffForHumans() }} @endif
                    </div>
                    @if(! empty($cookies['sessions']))
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            @foreach($cookies['sessions'] as $site => $loggedIn)
                                @if($loggedIn)
                                    <span class="badge bg-success-subtle text-success"><i class="fas fa-check me-1"></i>{{ $site }}: logged in</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger" title="Log in to {{ $site }} in your browser, then export cookies.txt again">
                                        <i class="fas fa-times me-1"></i>{{ $site }}: no login session — export again while logged in
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    @endif
                    <hr>
                @endif

                <p class="small mb-2">
                    To download <strong>private</strong> media from your own accounts (private videos, friends-only posts,
                    private groups, saved reels…), upload a <code>cookies.txt</code> exported from a browser where you are logged in:
                </p>
                <ol class="small mb-3">
                    <li>Install the browser extension <strong>“Get cookies.txt LOCALLY”</strong> (Chrome / Firefox).</li>
                    <li>Log in to Facebook / YouTube / Instagram… in that browser.</li>
                    <li>Open the site, click the extension and <em>Export</em> (Netscape format). You can export several sites into one file.</li>
                    <li>Upload the file here. Re-upload when a site logs you out.</li>
                </ol>
                <div class="alert alert-warning py-2 small mb-3">
                    <i class="fas fa-shield-alt me-1"></i>
                    Cookies give full access to those accounts. They are stored encrypted on this server and never shown again —
                    use this only on a server you trust. DRM-protected media still cannot be downloaded.
                </div>

                <form method="POST" action="{{ route('ut.media-downloader.cookies.store') }}" enctype="multipart/form-data"
                      id="mdCookiesForm" class="d-flex flex-wrap gap-2 align-items-center">
                    @csrf
                    <input type="file" name="cookies" accept=".txt,text/plain"
                           class="form-control form-control-sm @error('cookies') is-invalid @enderror" style="max-width:360px;" required>
                </form>
            </div>

            <div class="modal-footer">
                @if($cookies)
                    <form method="POST" action="{{ route('ut.media-downloader.cookies.destroy') }}" class="me-auto"
                          onsubmit="if (!confirm('Delete saved cookies? Private media will stop working.')) { event.stopPropagation(); return false; }">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-encodex-delete"><i class="fas fa-trash me-1"></i> Delete cookies</button>
                    </form>
                @endif
                <button type="button" class="btn btn-encodex-cancel btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="submit" form="mdCookiesForm" class="btn btn-sm btn-encodex-save">
                    <i class="fas fa-upload me-1"></i> Save cookies
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
@include('utility::partials.toast')
@if($errors->has('cookies'))
<script>
    document.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('mdCookiesModal')).show());
</script>
@endif
<script>
(() => {
    const routes = {
        analyze:  @js(route('ut.media-downloader.analyze')),
        setup:    @js(route('ut.media-downloader.setup')),
        setupInstall: @js(route('ut.media-downloader.setup.install')),
        download: @js(route('ut.media-downloader.download')),
        batchStart: @js(route('ut.media-downloader.batch.start')),
    };
    const csrf = @js(csrf_token());
    const $ = id => document.getElementById(id);
    // Elements inside a cloned template are addressed by data-md="…" (ids would repeat)
    const part = (root, name) => root.querySelector('[data-md="' + name + '"]');
    const clone = tplId => $(tplId).content.firstElementChild.cloneNode(true);

    let lastBatchDone = null;     // bulk download: whether the last poll said "done"

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
            body: JSON.stringify(body || {}),
        });
        return handle(res);
    }

    async function getJson(url) {
        return handle(await fetch(url, {headers: {'Accept': 'application/json'}}));
    }

    async function handle(res) {
        let json = {};
        try { json = await res.json(); } catch (e) {}
        if (!res.ok) {
            let msg = json.message || 'Something went wrong. Please try again.';
            if (res.status === 429) msg = 'Too many requests. Please wait a minute and try again.';
            if (json.errors) msg = Object.values(json.errors).flat().join('\n');
            const err = new Error(msg);
            err.reason = json.reason;
            err.data   = json;
            throw err;
        }
        return json;
    }

    function showError(el, message) {
        el.textContent = message;
        el.classList.toggle('d-none', !message);
    }

    // Messages go through the theme's toastr (shared helper: partials/toast)
    const notify = (type, message) => utNotify(type, message);

    function cell(text, cls) {
        const td = document.createElement('td');
        td.textContent = text ?? '–';
        if (cls) td.className = cls;
        return td;
    }

    // ── Analyze: every URL gets its own result card, several can run at once ──
    function normalizeUrl(url) {
        // "tiktok.com/@user/photo/1" → add https:// (other schemes are still rejected)
        if (!/^[a-z][a-z0-9+.-]*:/i.test(url) && /^(\/\/)?[\w-]+(\.[\w-]+)+(:\d+)?([\/?#]|$)/.test(url)) {
            url = 'https://' + url.replace(/^\/\//, '');
        }
        return /^https?:\/\//i.test(url) ? url : null;
    }

    // Input may hold one URL or several (separated by spaces / new lines)
    function analyzeText(text) {
        const parts = (text || '').split(/\s+/).filter(Boolean);
        if (!parts.length) return false;

        const urls = parts.map(normalizeUrl);
        if (urls.some(u => !u)) {
            notify('error', 'Please enter a valid http:// or https:// URL.');
            return false;
        }
        [...new Set(urls)].forEach(analyze);
        $('mdUrl').value = '';
        return true;
    }

    async function analyze(url) {
        const card = clone('mdResultTpl');
        const el = name => part(card, name);
        const result = {card, url, title: '', progress: null};

        el('url').textContent = url;
        el('url').href = url;
        el('close').addEventListener('click', () => {
            result.progress?.stop();
            card.remove();
        });
        $('mdResults').prepend(card);
        card.scrollIntoView({behavior: 'smooth', block: 'nearest'});

        try {
            const json = await postJson(routes.analyze, {url});
            result.url = json.data.url;
            if (json.kind === 'playlist') {
                renderPlaylist(result, json.data);
            } else {
                renderInfo(result, json.data);
            }
        } catch (err) {
            showError(el('error'), err.message);
            notify('error', err.message);
            // yt-dlp / FFmpeg missing: open Server setup, which can install them
            if (err.reason === 'binary_missing') openSetup();
        } finally {
            el('loading').classList.add('d-none');
        }
    }

    // A download started from a result card is tracked inside that card
    function startDownload(result, format, btn) {
        if (!result.progress) {
            result.progress = progressPanel(part(result.card, 'progressSlot'), ['border-top', 'pt-3', 'mt-4']);
        }
        result.progress.request(result.url, format, result.title, btn);
    }

    function formatRow(result, opt, isVideo) {
        const tr = document.createElement('tr');
        tr.appendChild(cell(opt.label, 'fw-semibold'));
        tr.appendChild(cell(opt.extension));
        if (isVideo) {
            tr.appendChild(cell(opt.resolution, 'd-none d-md-table-cell small'));
            tr.appendChild(cell(opt.fps, 'd-none d-md-table-cell small'));
            tr.appendChild(cell([opt.video_codec, opt.audio_codec].filter(Boolean).join(' + ') || null, 'd-none d-xl-table-cell small text-muted'));
        } else {
            tr.appendChild(cell(opt.audio_codec, 'd-none d-xl-table-cell small text-muted'));
        }
        tr.appendChild(cell(opt.filesize_human, 'small'));

        const td  = document.createElement('td');
        td.className = 'text-end';
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-sm btn-encodex-create';
        btn.innerHTML = '<i class="fas fa-download me-1"></i> Download';
        btn.addEventListener('click', () => startDownload(result, opt.key, btn));
        td.appendChild(btn);
        tr.appendChild(td);
        return tr;
    }

    function renderInfo(result, info) {
        const el = name => part(result.card, name);
        result.title = info.title;

        el('title').textContent    = info.title;
        el('platform').textContent = info.platform || '–';
        el('duration').textContent = info.duration || '–';
        el('uploader').textContent = info.uploader || '–';

        const hasThumb = !!info.thumbnail;
        el('thumbBox').classList.toggle('d-none', !hasThumb);
        el('thumbEmpty').classList.toggle('d-none', hasThumb);
        if (hasThumb) el('thumb').src = info.thumbnail;

        el('descWrap').classList.toggle('d-none', !info.description);
        el('description').textContent = info.description || '';

        const images    = info.formats.image || [];
        const hasImages = images.length > 0;

        for (const type of ['video', 'audio']) {
            const tbody = el(type + 'Rows');
            const rows = info.formats[type];
            // An image-only post (e.g. TikTok photos) shows just its images
            el(type + 'Section').classList.toggle('d-none', !rows.length && hasImages);
            if (!rows.length) {
                const tr = document.createElement('tr');
                const td = cell(`No ${type} formats available.`, 'text-muted small');
                td.colSpan = 7;
                tr.appendChild(td);
                tbody.appendChild(tr);
            }
            rows.forEach(opt => tbody.appendChild(formatRow(result, opt, type === 'video')));
        }

        renderImages(result, images);
        el('info').classList.remove('d-none');
    }

    function renderImages(result, images) {
        const el     = name => part(result.card, name);
        const all    = images.find(o => o.key === 'images-all');
        const single = images.filter(o => o.key !== 'images-all');
        const grid   = el('imageGrid');

        el('imageSection').classList.toggle('d-none', single.length === 0);
        el('imageCount').textContent = single.length ? '(' + single.length + ')' : '';
        el('imagesAllBtn').classList.toggle('d-none', !all);
        if (all) el('imagesAllBtn').onclick = () => startDownload(result, all.key, el('imagesAllBtn'));

        single.forEach(opt => {
            const item = document.createElement('div');
            item.style.width = '120px';

            // Post images are shown clearly (only the main thumbnail on the left is blurred)
            const box = document.createElement('div');
            box.className = 'md-img-tile rounded border';
            const img = document.createElement('img');
            img.src = opt.preview;
            img.alt = opt.label;
            img.loading = 'lazy';
            img.referrerPolicy = 'no-referrer';
            box.appendChild(img);

            const meta = document.createElement('div');
            meta.className = 'd-flex justify-content-between align-items-center mt-1';
            const text = document.createElement('span');
            text.className = 'small fw-semibold';
            text.textContent = opt.label.replace('Image ', '#');
            text.title = opt.label + (opt.resolution ? ' · ' + opt.resolution : '') + ' · ' + opt.extension;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm btn-encodex-create py-0 px-2';
            btn.title = 'Download ' + opt.label;
            btn.innerHTML = '<i class="fas fa-download"></i>';
            btn.addEventListener('click', () => startDownload(result, opt.key, btn));
            meta.append(text, btn);

            const res = document.createElement('div');
            res.className = 'text-muted';
            res.style.fontSize = '.75rem';
            res.textContent = [opt.resolution, opt.extension].filter(Boolean).join(' · ');

            item.append(box, meta, res);
            grid.appendChild(item);
        });
    }

    // Paste & Analyze: read the clipboard and analyze it
    // (Clipboard API needs HTTPS/localhost; otherwise fall back to the user's Ctrl+V)
    $('mdPasteAnalyzeBtn').addEventListener('click', async () => {
        try {
            if (!navigator.clipboard?.readText) throw new Error('unsupported');
            const text = await navigator.clipboard.readText();
            if (!text.trim()) {
                notify('error', 'Clipboard is empty.');
                return;
            }
            $('mdUrl').value = text.trim();
            analyzeText(text);
        } catch (err) {
            $('mdUrl').focus();
            $('mdUrl').select();
            notify('info', 'Press Ctrl+V to paste the URL — it will be analyzed automatically.');
        }
    });

    // Pasting into the field analyzes right away — also while other links are still analyzing
    $('mdUrl').addEventListener('paste', (e) => {
        const text = e.clipboardData?.getData('text');
        if (!text?.trim()) return;
        e.preventDefault();
        $('mdUrl').value = text.trim();
        analyzeText(text);
    });

    $('mdAnalyzeForm').addEventListener('submit', (e) => {
        e.preventDefault();
        hidePageLoader();
        if (!analyzeText($('mdUrl').value) && !$('mdUrl').value.trim()) {
            notify('error', 'Please enter a valid http:// or https:// URL.');
        }
    });

    // ── Server setup ───────────────────────────────────────────────────────────
    const setupIcons = {
        ok:   '<i class="fas fa-check-circle text-success"></i>',
        warn: '<i class="fas fa-exclamation-triangle text-warning"></i>',
        fail: '<i class="fas fa-times-circle text-danger"></i>',
    };

    function renderSetup(state) {
        const tbody = $('mdSetupRows');
        tbody.replaceChildren();
        (state.checks || []).forEach(c => {
            const tr = document.createElement('tr');
            const icon = document.createElement('td');
            icon.style.width = '28px';
            icon.innerHTML = setupIcons[c.status] || '';
            tr.append(icon, cell(c.name, 'small fw-semibold text-nowrap'), cell(c.detail, 'small text-break'));
            tbody.appendChild(tr);
        });
        $('mdSetupInstall').disabled = !state.can_install;
        showError($('mdSetupNote'), state.install_note || '');
    }

    function setInstallBusy(busy) {
        const btn = $('mdSetupInstall');
        btn.disabled = busy;
        btn.querySelector('.md-btn-label').classList.toggle('d-none', busy);
        btn.querySelector('.md-btn-busy').classList.toggle('d-none', !busy);
    }

    async function loadSetup() {
        $('mdSetupLoading').classList.remove('d-none');
        try {
            renderSetup(await getJson(routes.setup));
        } catch (err) {
            showError($('mdSetupNote'), err.message);
        } finally {
            $('mdSetupLoading').classList.add('d-none');
        }
    }

    function openSetup() {
        bootstrap.Modal.getOrCreateInstance($('mdSetupModal')).show();
    }

    $('mdSetupModal').addEventListener('show.bs.modal', loadSetup);

    $('mdSetupInstall').addEventListener('click', async () => {
        setInstallBusy(true);
        $('mdSetupResults').replaceChildren();
        let state;
        try {
            state = await postJson(routes.setupInstall);
        } catch (err) {
            state = err.data && err.data.checks ? err.data : null;
            notify('error', err.message);
        }
        setInstallBusy(false);
        if (!state) return;

        renderSetup(state);
        (state.results || []).forEach(r => {
            const div = document.createElement('div');
            div.className = r.ok ? 'text-success' : 'text-danger';
            div.textContent = (r.ok ? '✓ ' : '✗ ') + r.tool + ': ' + r.message;
            $('mdSetupResults').appendChild(div);
        });
        const failed = (state.results || []).filter(r => !r.ok);
        if (state.results && !failed.length) notify('success', 'Tools installed. You can analyze links now.');
    });

    // ── Download progress panel (one per result card, plus one for history downloads) ──
    function progressPanel(container, classes = []) {
        const root = clone('mdProgressTpl');
        const el = name => part(root, name);
        root.classList.add(...classes);
        container.appendChild(root);

        let timer = null;
        let lastStatus = null;   // last status seen (for one-time toasts)

        function show() {
            container.classList.remove('d-none');
            root.scrollIntoView({behavior: 'smooth', block: 'nearest'});
        }

        function render(s) {
            const bar = el('bar');
            const active = ['pending', 'analyzing', 'downloading'].includes(s.status);
            const known  = s.progress !== null && s.progress !== undefined && s.status === 'downloading';

            el('title').textContent = s.title || '';
            el('stage').textContent = {
                pending: 'Waiting to start…', analyzing: 'Preparing…', completed: 'Download ready',
                failed: 'Download failed', cancelled: 'Download cancelled',
            }[s.status] || (s.stage ? s.stage + '…' : 'Downloading…');

            // Unknown progress → indeterminate striped bar, never an invented percentage
            el('percent').textContent = known ? Math.floor(s.progress) + '%' : (s.status === 'completed' ? '100%' : '');
            bar.style.width = known ? s.progress + '%' : '100%';
            bar.classList.toggle('progress-bar-striped', active && !known);
            bar.classList.toggle('progress-bar-animated', active && !known);
            bar.classList.toggle('bg-success', s.status === 'completed');
            bar.classList.toggle('bg-danger', s.status === 'failed');
            bar.classList.toggle('bg-warning', s.status === 'cancelled');

            el('speed').textContent = s.speed || '–';
            el('eta').textContent   = s.eta || '–';
            el('size').textContent  = s.filesize || '–';

            showError(el('error'), s.status === 'failed' ? (s.error_message || 'Unable to download this media.') : '');

            // Toast only when the status changes, not on every poll
            if (s.status !== lastStatus) {
                if (s.status === 'completed') notify('success', 'Download ready: ' + (s.title || 'file'));
                if (s.status === 'failed') notify('error', s.error_message || 'Unable to download this media.');
                if (s.status === 'cancelled') notify('warning', 'Download cancelled.');
                lastStatus = s.status;
            }
            el('cancel').classList.toggle('d-none', !active);
            el('save').classList.toggle('d-none', !s.file_url);
            if (s.file_url) el('save').href = s.file_url;
        }

        function track(statusUrl, cancelUrl, initial) {
            clearTimeout(timer);
            lastStatus = null;
            show();
            el('cancel').onclick = async () => {
                el('cancel').disabled = true;
                try { render((await postJson(cancelUrl)).data); } catch (err) { notify('error', err.message); }
                el('cancel').disabled = false;
            };

            let autoSaved = false;
            let pollFailing = false;
            const poll = async () => {
                try {
                    const s = (await getJson(statusUrl)).data;
                    render(s);
                    if (s.status === 'completed' && s.file_url && !autoSaved) {
                        autoSaved = true;
                        saveFile(s.file_url);
                    }
                    if (['pending', 'analyzing', 'downloading'].includes(s.status)) {
                        timer = setTimeout(poll, 1500);
                    }
                } catch (err) {
                    if (!pollFailing) notify('error', err.message); // once per outage, not every retry
                    pollFailing = true;
                    timer = setTimeout(poll, 5000);
                    return;
                }
                pollFailing = false;
            };

            if (initial) render(initial);
            poll();
        }

        async function request(url, format, title, btn) {
            btn.disabled = true;

            // Show progress immediately, before the server has answered
            clearTimeout(timer);
            lastStatus = null;
            show();
            render({status: 'pending', progress: null, title});

            try {
                const json = await postJson(routes.download, {url, format});
                track(json.status_url, json.cancel_url, json.data);
            } catch (err) {
                render({status: 'failed', progress: null, title, error_message: err.message});
            } finally {
                btn.disabled = false;
            }
        }

        return {track, request, stop: () => clearTimeout(timer)};
    }

    const historyProgress = progressPanel($('mdProgressCard'));

    // History "Resume" / "Re-download" (same link + format as before; the server re-validates both)
    window.mdTrack = (statusUrl, cancelUrl, initial) => historyProgress.track(statusUrl, cancelUrl, initial);
    window.mdRedownload = function (btn) {
        historyProgress.request(btn.dataset.url, btn.dataset.format, btn.dataset.title, btn);
    };

    // Trigger the browser download without navigating away: a navigation would fire
    // "beforeunload" and the theme's full-page loader would cover the progress card.
    function saveFile(url) {
        const a = document.createElement('a');
        a.href = url;
        a.download = '';
        a.className = 'no-loader';
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        a.remove();
        hidePageLoader();
    }

    function hidePageLoader() {
        if (window.XLoader && typeof window.XLoader.hide === 'function') {
            setTimeout(() => window.XLoader.hide(), 300);
        }
    }

    // ── Lists (playlist / channel / profile) ──────────────────────────────────
    const statusBadge = {
        pending: 'secondary', analyzing: 'info', downloading: 'primary',
        completed: 'success', failed: 'danger', cancelled: 'warning',
    };

    // Small blurred thumbnail with a press-and-hold button (list and bulk rows)
    function peekThumb(src) {
        const box = document.createElement('div');
        box.className = 'md-media md-media-sm rounded';
        box.style.cssText = 'width:76px;height:43px;';
        const img = document.createElement('img');
        img.src = src;
        img.loading = 'lazy';
        img.referrerPolicy = 'no-referrer';
        img.alt = '';
        img.style.cssText = 'width:76px;height:43px;object-fit:cover;';
        const peek = document.createElement('button');
        peek.type = 'button';
        peek.className = 'md-peek';
        peek.title = 'Press and hold to view';
        peek.setAttribute('aria-label', 'Press and hold to view');
        peek.innerHTML = '<i class="fas fa-eye"></i>';
        box.append(img, peek);
        return box;
    }

    function renderPlaylist(result, pl) {
        const card = result.card;
        const el = name => part(card, name);
        const checks = () => [...card.querySelectorAll('.md-pl-check')];

        function updateSelectedCount() {
            const boxes   = checks();
            const checked = boxes.filter(b => b.checked).length;
            el('plSelected').textContent = checked;
            el('plStart').disabled = checked === 0;
            el('plAll').checked = boxes.length > 0 && checked === boxes.length;
        }

        result.title = pl.title;
        el('plTitle').textContent    = pl.title;
        el('plPlatform').textContent = pl.platform || '';
        el('plUploader').textContent = pl.uploader ? '· ' + pl.uploader : '';
        const photos = pl.photos || 0;
        const videos = pl.count - photos;
        el('plCount').textContent = photos
            ? videos + ' video' + (videos === 1 ? '' : 's') + ' · ' + photos + ' photo post' + (photos === 1 ? '' : 's')
            : pl.count + ' item' + (pl.count === 1 ? '' : 's');
        el('plTypeFilter').classList.toggle('d-none', !(photos && videos));
        el('plTruncated').classList.toggle('d-none', !pl.truncated);

        const tbody = el('plRows');

        pl.entries.forEach(entry => {
            const tr = document.createElement('tr');

            const tdCheck = document.createElement('td');
            tdCheck.style.width = '32px';
            if (entry.kind === 'video') {
                const cb = document.createElement('input');
                cb.type = 'checkbox';
                cb.className = 'form-check-input md-pl-check';
                cb.value = entry.id;
                cb.dataset.media = entry.media || '';
                cb.checked = true;
                cb.addEventListener('change', updateSelectedCount);
                tdCheck.appendChild(cb);
            } else {
                tdCheck.innerHTML = '<i class="fas fa-folder text-warning"></i>';
            }
            tr.appendChild(tdCheck);

            const tdThumb = document.createElement('td');
            tdThumb.style.width = '84px';
            if (entry.thumbnail) tdThumb.appendChild(peekThumb(entry.thumbnail));
            tr.appendChild(tdThumb);

            tr.appendChild(cell(entry.title, 'small text-break'));

            // Type column: Video / Photos / List (– when it can't be told from the listing)
            const tdType = document.createElement('td');
            tdType.className = 'text-nowrap';
            const types = {
                video:  ['bg-primary', 'fa-film', 'Video'],
                images: ['bg-warning text-dark', 'fa-images', 'Photos'],
                list:   ['bg-secondary', 'fa-folder', 'List'],
            };
            const type = types[entry.kind === 'playlist' ? 'list' : entry.media];
            if (type) {
                const badge = document.createElement('span');
                badge.className = 'badge ' + type[0];
                badge.innerHTML = '<i class="fas ' + type[1] + ' me-1"></i>' + type[2];
                tdType.appendChild(badge);
            } else {
                tdType.textContent = '–';
                tdType.classList.add('text-muted', 'small');
            }
            tr.appendChild(tdType);

            tr.appendChild(cell(entry.kind === 'playlist' ? null : entry.duration, 'small text-muted text-nowrap'));

            const tdAction = document.createElement('td');
            tdAction.className = 'text-end';
            if (entry.kind === 'playlist' && entry.url) {
                // Nested list (e.g. a channel's Videos / Shorts tab): open it in a new card
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-sm btn-encodex-show';
                btn.innerHTML = '<i class="fas fa-folder-open me-1"></i> Open';
                btn.addEventListener('click', () => analyze(entry.url));
                tdAction.appendChild(btn);
            }
            tr.appendChild(tdAction);

            tbody.appendChild(tr);
        });

        // "Only videos" / "Only photos": select just that type
        el('plTypeFilter').querySelectorAll('[data-select]').forEach(btn => btn.addEventListener('click', () => {
            checks().forEach(b => b.checked = b.dataset.media === btn.dataset.select);
            updateSelectedCount();
        }));

        el('plAll').addEventListener('change', (e) => {
            checks().forEach(b => b.checked = e.target.checked);
            updateSelectedCount();
        });

        el('plStart').addEventListener('click', async () => {
            const entries = checks().filter(b => b.checked).map(b => b.value);
            if (!entries.length) return;

            const btn = el('plStart');
            btn.disabled = true;
            try {
                const json = await postJson(routes.batchStart, {url: result.url, entries, format: el('plPreset').value});
                notify('info', entries.length + ' item(s) queued for download.');
                mdTrackBatch(json.status_url, json.cancel_url);
            } catch (err) {
                notify('error', err.message);
            } finally {
                btn.disabled = false;
            }
        });

        updateSelectedCount();
        el('list').classList.remove('d-none');
    }

    let batchTimer = null;

    function renderBatch(b) {
        const c        = b.counts;
        const finished = c.completed + c.failed + c.cancelled;
        const pct      = c.total ? Math.round(finished / c.total * 100) : 0;
        const bar      = $('mdBatchBar');

        $('mdBatchTitle').textContent   = b.title || '';
        $('mdBatchStage').textContent   = b.done ? 'Bulk download finished' : 'Bulk downloading…';
        $('mdBatchCount').textContent   = finished + ' / ' + c.total;
        $('mdBatchSummary').textContent = c.completed + ' completed · ' + c.failed + ' failed · '
            + c.cancelled + ' cancelled · ' + c.active + ' remaining';

        bar.style.width = pct + '%';
        bar.classList.toggle('progress-bar-striped', !b.done);
        bar.classList.toggle('progress-bar-animated', !b.done);
        bar.classList.toggle('bg-success', b.done && c.failed === 0 && c.cancelled === 0);

        $('mdBatchCancel').classList.toggle('d-none', b.done);
        $('mdBatchZip').classList.toggle('d-none', !b.zip_url);
        $('mdBatchRetry').classList.toggle('d-none', !b.retry_url);
        $('mdBatchRetry').dataset.url = b.retry_url || '';

        // Same failure reason for every failed item → show it once, clearly
        const reasons = [...new Set(b.items.filter(i => i.status === 'failed').map(i => i.error_message).filter(Boolean))];
        showError($('mdBatchError'), b.done && reasons.length === 1 ? reasons[0] : '');

        if (lastBatchDone === false && b.done) {
            const msg = 'Bulk download finished: ' + c.completed + ' of ' + c.total + ' downloaded'
                + (c.failed ? ', ' + c.failed + ' failed' : '') + (c.cancelled ? ', ' + c.cancelled + ' cancelled' : '') + '.';
            notify(c.completed === 0 ? 'error' : (c.failed || c.cancelled ? 'warning' : 'success'), msg);
        }
        lastBatchDone = b.done;
        if (b.zip_url) $('mdBatchZip').href = b.zip_url;

        const tbody = $('mdBatchRows');
        tbody.replaceChildren();
        b.items.forEach((item, index) => {
            const tr = document.createElement('tr');

            // Blurred preview, like the list rows (press and hold to view)
            const tdThumb = document.createElement('td');
            tdThumb.style.width = '84px';
            if (item.thumbnail) tdThumb.appendChild(peekThumb(item.thumbnail));
            tr.appendChild(tdThumb);

            // Untitled until the item is analyzed (some listings carry no titles)
            const tdTitle = cell(item.title || 'Item ' + (index + 1), 'small text-break');
            if (item.status === 'failed' && item.error_message) {
                const why = document.createElement('div');
                why.className = 'text-danger';
                why.style.fontSize = '.75rem';
                why.textContent = item.error_message.split('\n')[0];
                tdTitle.appendChild(why);
            }
            tr.appendChild(tdTitle);

            const tdStatus = document.createElement('td');
            tdStatus.className = 'text-nowrap small';
            const badge = document.createElement('span');
            badge.className = 'badge bg-' + (statusBadge[item.status] || 'secondary');
            badge.textContent = item.status === 'downloading' && item.progress !== null
                ? Math.floor(item.progress) + '%'
                : item.status.charAt(0).toUpperCase() + item.status.slice(1);
            if (item.error_message) badge.title = item.error_message;
            tdStatus.appendChild(badge);
            if (item.status === 'downloading' && item.speed) {
                tdStatus.appendChild(document.createTextNode(' ' + item.speed));
            }
            tr.appendChild(tdStatus);

            tr.appendChild(cell(item.filesize, 'small text-muted text-nowrap'));

            const tdAction = document.createElement('td');
            tdAction.className = 'text-end';
            if (item.file_url) {
                const a = document.createElement('a');
                a.href = item.file_url;
                a.className = 'btn btn-sm btn-encodex-save no-loader';
                a.setAttribute('download', '');
                a.innerHTML = '<i class="fas fa-download"></i>';
                tdAction.appendChild(a);
            }
            tr.appendChild(tdAction);
            tbody.appendChild(tr);
        });
    }

    window.mdTrackBatch = function (statusUrl, cancelUrl) {
        clearTimeout(batchTimer);
        $('mdBatch').classList.remove('d-none');
        $('mdBatch').scrollIntoView({behavior: 'smooth', block: 'nearest'});
        showError($('mdBatchError'), '');
        lastBatchDone = false; // only running batches are tracked, so the finish toast always fires once

        $('mdBatchCancel').onclick = async () => {
            if (!confirm('Cancel all remaining items of this bulk download?')) return;
            $('mdBatchCancel').disabled = true;
            try {
                renderBatch((await postJson(cancelUrl)).data);
                notify('warning', 'Remaining items cancelled.');
            } catch (err) { notify('error', err.message); }
            $('mdBatchCancel').disabled = false;
        };

        $('mdBatchRetry').onclick = async () => {
            $('mdBatchRetry').disabled = true;
            try {
                renderBatch((await postJson($('mdBatchRetry').dataset.url)).data);
                clearTimeout(batchTimer);
                batchTimer = setTimeout(poll, 1500);
                notify('info', 'Failed items queued again.');
            } catch (err) { notify('error', err.message); }
            $('mdBatchRetry').disabled = false;
        };

        let pollFailing = false;
        const poll = async () => {
            try {
                const b = (await getJson(statusUrl)).data;
                renderBatch(b);
                pollFailing = false;
                if (!b.done) batchTimer = setTimeout(poll, 2000);
            } catch (err) {
                if (!pollFailing) notify('error', err.message);
                pollFailing = true;
                batchTimer = setTimeout(poll, 5000);
            }
        };
        poll();
    };

    @if($activeBatch)
        mdTrackBatch(
            @js(route('ut.media-downloader.batch.status', $activeBatch)),
            @js(route('ut.media-downloader.batch.cancel', $activeBatch))
        );
    @endif

    // ── Blurred media: visible only while the centre button is held down ─────
    const hidePeeks = () => document.querySelectorAll('.md-media.md-reveal').forEach(el => el.classList.remove('md-reveal'));

    document.addEventListener('pointerdown', (e) => {
        const peek = e.target.closest('.md-peek');
        if (!peek) return;
        e.preventDefault();
        peek.closest('.md-media').classList.add('md-reveal');
        try { peek.setPointerCapture(e.pointerId); } catch (_) {}
    });
    ['pointerup', 'pointercancel', 'lostpointercapture'].forEach(t => document.addEventListener(t, hidePeeks));
    window.addEventListener('blur', hidePeeks);

    // Keyboard: hold Space / Enter on the focused button
    document.addEventListener('keydown', (e) => {
        const peek = e.target.closest && e.target.closest('.md-peek');
        if (peek && (e.key === ' ' || e.key === 'Enter')) {
            e.preventDefault();
            peek.closest('.md-media').classList.add('md-reveal');
        }
    });
    document.addEventListener('keyup', (e) => {
        if (e.target.closest && e.target.closest('.md-peek')) hidePeeks();
    });

    // No long-press menu / "save image" on the blurred media
    document.addEventListener('contextmenu', (e) => {
        if (e.target.closest('.md-media')) e.preventDefault();
    });

})();
</script>
@endpush
