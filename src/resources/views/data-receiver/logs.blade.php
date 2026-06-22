@extends('me::master')

@section('title', 'Data Receiver — Logs')

@push('buttons')
    <a href="{{ route('ut.data-receiver') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
    <a href="{{ route('ut.data-receiver.export.csv', request()->query()) }}" class="btn btn-sm btn-outline-success ms-1">
        <i class="fas fa-file-csv me-1"></i> Export CSV
    </a>
@endpush

@section('content')

{{-- Filters --}}
<div class="card glass-card p-3 mb-3">
    <form method="GET" action="{{ route('ut.data-receiver.logs') }}" class="row g-2 align-items-end">
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold mb-1">Method</label>
            <select name="method" class="form-select form-select-sm">
                <option value="">All</option>
                @foreach(['GET','POST','PUT','PATCH','DELETE','OPTIONS'] as $m)
                    <option value="{{ $m }}" @selected(($filters['method'] ?? '') === $m)>{{ $m }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold mb-1">IP</label>
            <input type="text" name="ip" class="form-control form-control-sm"
                   placeholder="192.168." value="{{ $filters['ip'] ?? '' }}">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold mb-1">Content-Type</label>
            <input type="text" name="content_type" class="form-control form-control-sm"
                   placeholder="application/json" value="{{ $filters['content_type'] ?? '' }}">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold mb-1">Has Files</label>
            <select name="has_files" class="form-select form-select-sm">
                <option value="">Any</option>
                <option value="1" @selected(($filters['has_files'] ?? '') === '1')>Yes</option>
                <option value="0" @selected(($filters['has_files'] ?? '') === '0')>No</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold mb-1">From</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold mb-1">To</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}">
        </div>
        <div class="col-12">
            <div class="d-flex gap-2">
                <input type="text" name="keyword" class="form-control form-control-sm flex-grow-1"
                       placeholder="Search IP, path, body, user agent..." value="{{ $filters['keyword'] ?? '' }}">
                <button type="submit" class="btn btn-sm btn-primary px-3">
                    <i class="fas fa-search me-1"></i> Filter
                </button>
                <a href="{{ route('ut.data-receiver.logs') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="card glass-card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:80px;">Method</th>
                    <th>Path</th>
                    <th style="width:130px;">IP</th>
                    <th style="width:170px;">Content-Type</th>
                    <th style="width:75px;">Size</th>
                    <th style="width:60px;">Files</th>
                    <th style="width:140px;">Received</th>
                    <th style="width:70px;"></th>
                </tr>
            </thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>
                        <span class="badge
                            @if($log->method === 'GET') bg-primary
                            @elseif($log->method === 'POST') bg-success
                            @elseif($log->method === 'PUT') bg-warning text-dark
                            @elseif($log->method === 'PATCH') bg-info text-dark
                            @elseif($log->method === 'DELETE') bg-danger
                            @else bg-secondary
                            @endif">{{ $log->method }}</span>
                    </td>
                    <td>
                        <a href="{{ route('ut.data-receiver.log.show', $log->uuid) }}"
                           class="text-decoration-none fw-semibold" title="{{ $log->path }}">
                            {{ Str::limit($log->path, 45) }}
                        </a>
                        @if($log->is_json)
                            <span class="badge bg-success bg-opacity-10 text-success ms-1" style="font-size:.65rem;">JSON</span>
                        @endif
                    </td>
                    <td><code class="small">{{ $log->ip_address ?? '—' }}</code></td>
                    <td class="small text-muted text-truncate" style="max-width:170px;" title="{{ $log->content_type }}">
                        {{ $log->short_content_type }}
                    </td>
                    <td class="small text-muted">{{ $log->formatted_size }}</td>
                    <td class="text-center">
                        @if($log->has_files)
                            <span class="badge bg-info text-dark">
                                {{ $log->uploadedFiles->count() }} <i class="fas fa-paperclip"></i>
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="small text-muted">{{ $log->received_at?->diffForHumans() }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('ut.data-receiver.log.show', $log->uuid) }}"
                               class="btn btn-xs btn-outline-primary btn-sm" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <form method="POST" action="{{ route('ut.data-receiver.log.destroy', $log->uuid) }}"
                                  onsubmit="return confirm('Delete this log and its files?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-outline-danger btn-sm" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                        No logs found.
                        @if(array_filter($filters ?? []))
                            <a href="{{ route('ut.data-receiver.logs') }}" class="small d-block mt-1">Clear filters</a>
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
        <div class="px-3 py-2 border-top">
            {{ $logs->links() }}
        </div>
    @endif
</div>

@endsection
