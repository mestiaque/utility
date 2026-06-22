@extends('me::master')

@section('title', 'Log Detail')

@push('buttons')
    <a href="{{ route('ut.data-receiver.logs') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
    <button class="btn btn-sm btn-outline-secondary ms-1" onclick="copyJson()">
        <i class="fas fa-copy me-1"></i> Copy JSON
    </button>
    <form method="POST" action="{{ route('ut.data-receiver.log.destroy', $log->uuid) }}"
          class="d-inline ms-1" onsubmit="return confirm('Delete this log and all its files?')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger">
            <i class="fas fa-trash me-1"></i> Delete
        </button>
    </form>
@endpush

@section('content')

{{-- Page header --}}
<div class="mb-3">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="badge fs-6
            @if($log->method === 'GET') bg-primary
            @elseif($log->method === 'POST') bg-success
            @elseif($log->method === 'PUT') bg-warning text-dark
            @elseif($log->method === 'PATCH') bg-info text-dark
            @elseif($log->method === 'DELETE') bg-danger
            @else bg-secondary
            @endif">{{ $log->method }}</span>
        <code class="fs-6">{{ $log->path }}</code>
        @if($log->is_json)   <span class="badge bg-success">JSON</span>   @endif
        @if($log->is_xml)    <span class="badge bg-warning text-dark">XML</span> @endif
        @if($log->is_binary) <span class="badge bg-info text-dark">Binary</span> @endif
    </div>
    <div class="small text-muted mt-1 d-flex gap-3 flex-wrap">
        <span><i class="fas fa-clock me-1"></i>{{ $log->received_at?->format('Y-m-d H:i:s') }}</span>
        <span><i class="fas fa-globe me-1"></i>{{ $log->ip_address }}</span>
        <span><i class="fas fa-weight me-1"></i>{{ $log->formatted_size }}</span>
    </div>
</div>

{{-- Tabs --}}
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-overview">Overview</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-headers">Headers</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-body">Body</button>
    </li>
    @if($log->has_files)
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-files">
            Files <span class="badge bg-info text-dark ms-1">{{ $log->uploadedFiles->count() }}</span>
        </button>
    </li>
    @endif
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-raw">Raw JSON</button>
    </li>
</ul>

<div class="tab-content">

    {{-- Overview --}}
    <div class="tab-pane fade show active" id="tab-overview">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card glass-card">
                    <div class="card-header fw-semibold small">Request Info</div>
                    <table class="table table-sm mb-0">
                        <tr><th class="ps-3" style="width:140px;">UUID</th><td><code class="small">{{ $log->uuid }}</code></td></tr>
                        <tr><th class="ps-3">Full URL</th><td class="text-break small">{{ $log->full_url }}</td></tr>
                        <tr><th class="ps-3">Host</th><td>{{ $log->host }}</td></tr>
                        <tr><th class="ps-3">Scheme</th><td>{{ $log->scheme }}</td></tr>
                        <tr><th class="ps-3">IP</th><td><code>{{ $log->ip_address }}</code></td></tr>
                        <tr><th class="ps-3">User Agent</th><td class="small text-break">{{ $log->user_agent ?: '—' }}</td></tr>
                        <tr><th class="ps-3">Referer</th><td class="small">{{ $log->referer ?: '—' }}</td></tr>
                        <tr><th class="ps-3">Received At</th><td class="small">{{ $log->received_at?->format('Y-m-d H:i:s') }}</td></tr>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card glass-card">
                    <div class="card-header fw-semibold small">Content Info</div>
                    <table class="table table-sm mb-0">
                        <tr><th class="ps-3" style="width:140px;">Content-Type</th><td class="small text-break">{{ $log->content_type ?: '—' }}</td></tr>
                        <tr><th class="ps-3">Content-Length</th><td>{{ $log->content_length ? number_format($log->content_length).' B' : '—' }}</td></tr>
                        <tr><th class="ps-3">Request Size</th><td>{{ $log->formatted_size }}</td></tr>
                        <tr><th class="ps-3">Has Files</th><td>{{ $log->has_files ? 'Yes ('.$log->uploadedFiles->count().')' : 'No' }}</td></tr>
                        <tr><th class="ps-3">Flags</th>
                            <td>
                                @if($log->is_json)   <span class="badge bg-success me-1">JSON</span>   @endif
                                @if($log->is_xml)    <span class="badge bg-warning text-dark me-1">XML</span> @endif
                                @if($log->is_binary) <span class="badge bg-info text-dark me-1">Binary</span> @endif
                                @if(!$log->is_json && !$log->is_xml && !$log->is_binary) <span class="text-muted small">—</span> @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($log->query_params)
            <div class="col-12">
                <div class="card glass-card">
                    <div class="card-header fw-semibold small">Query Parameters</div>
                    <pre class="p-3 mb-0 rounded" style="background:#1e2a3a;color:#e2e8f0;font-size:.8rem;">{{ json_encode($log->query_params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
            @endif

            @if($log->parsed_body)
            <div class="col-12">
                <div class="card glass-card">
                    <div class="card-header fw-semibold small">Parsed JSON Body</div>
                    <pre class="p-3 mb-0 rounded" style="background:#1e2a3a;color:#e2e8f0;font-size:.8rem;">{{ json_encode($log->parsed_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
            @endif

            @if($log->form_fields)
            <div class="col-12">
                <div class="card glass-card">
                    <div class="card-header fw-semibold small">Form Fields</div>
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th class="ps-3">Field</th><th>Value</th></tr></thead>
                        <tbody>
                        @foreach($log->form_fields as $k => $v)
                            <tr>
                                <th class="ps-3"><code>{{ $k }}</code></th>
                                <td class="small text-break">{{ is_array($v) ? json_encode($v) : $v }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Headers --}}
    <div class="tab-pane fade" id="tab-headers">
        <div class="card glass-card">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th class="ps-3" style="width:35%;">Header</th><th>Value</th></tr></thead>
                <tbody>
                @foreach($log->headers ?? [] as $name => $values)
                    <tr>
                        <th class="ps-3 font-monospace small">{{ $name }}</th>
                        <td class="small text-break">{{ implode(', ', (array)$values) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Body --}}
    <div class="tab-pane fade" id="tab-body">
        <div class="card glass-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold small">Raw Body</span>
                <button class="btn btn-sm btn-outline-secondary" onclick="copyPre('rawBody', this)">
                    <i class="fas fa-copy"></i> Copy
                </button>
            </div>
            @if($log->raw_body)
                <pre class="p-3 mb-0 rounded" id="rawBody"
                     style="background:#1e2a3a;color:#e2e8f0;font-size:.8rem;max-height:500px;overflow:auto;">{{ $log->raw_body }}</pre>
            @else
                <p class="text-muted p-3 mb-0 small">No body content.</p>
            @endif
        </div>
    </div>

    {{-- Files --}}
    @if($log->has_files)
    <div class="tab-pane fade" id="tab-files">
        <div class="row g-3">
            @foreach($log->uploadedFiles as $file)
            <div class="col-md-4 col-lg-3">
                <div class="card glass-card h-100">
                    <div style="height:130px;overflow:hidden;background:#111;border-radius:.375rem .375rem 0 0;">
                        @if($file->is_image)
                            <img src="{{ $file->public_url }}" class="w-100 h-100" style="object-fit:cover;">
                        @elseif($file->is_video)
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <i class="fas fa-play-circle text-light fa-3x"></i>
                            </div>
                        @elseif($file->is_audio)
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <i class="fas fa-music text-info fa-3x"></i>
                            </div>
                        @elseif($file->is_pdf)
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <i class="fas fa-file-pdf text-danger fa-3x"></i>
                            </div>
                        @else
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <i class="fas fa-file text-secondary fa-3x"></i>
                            </div>
                        @endif
                    </div>
                    <div class="p-2">
                        <p class="small fw-semibold mb-1 text-truncate" title="{{ $file->original_name }}">
                            {{ $file->original_name }}
                        </p>
                        <div class="d-flex gap-1 mb-2">
                            <span class="badge bg-secondary" style="font-size:.65rem;">{{ strtoupper($file->extension) }}</span>
                            <span class="badge bg-light text-dark border" style="font-size:.65rem;">{{ $file->formatted_size }}</span>
                        </div>
                        <a href="{{ route('ut.data-receiver.file.download', $file->uuid) }}"
                           class="btn btn-sm btn-outline-success w-100">
                            <i class="fas fa-download me-1"></i> Download
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Raw JSON --}}
    <div class="tab-pane fade" id="tab-raw">
        <div class="card glass-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold small">Complete Log as JSON</span>
                <button class="btn btn-sm btn-outline-secondary" onclick="copyPre('fullJson', this)">
                    <i class="fas fa-copy"></i> Copy
                </button>
            </div>
            <pre class="p-3 mb-0 rounded" id="fullJson"
                 style="background:#1e2a3a;color:#e2e8f0;font-size:.75rem;max-height:600px;overflow:auto;">{{ json_encode($log->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function copyJson() { copyPre('fullJson', document.querySelector('[onclick="copyJson()"]')); }

function copyPre(id, btn) {
    navigator.clipboard.writeText(document.getElementById(id)?.innerText ?? '').then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check text-success"></i> Copied';
        setTimeout(() => btn.innerHTML = orig, 1500);
    });
}
</script>
@endpush
