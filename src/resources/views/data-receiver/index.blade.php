@extends('me::master')

@section('title', 'Data Receiver')

@push('buttons')
    <a href="{{ route('ut.data-receiver.logs') }}" class="btn btn-sm btn-encodex-list">
        <i class="fas fa-list-alt me-1"></i> Logs
        @if($stats['total'] > 0)
            <span class="badge bg-white text-dark ms-1">{{ number_format($stats['total']) }}</span>
        @endif
    </a>
    <a href="{{ route('ut.data-receiver.files') }}" class="btn btn-sm btn-outline-info ms-1">
        <i class="fas fa-folder-open me-1"></i> Files
    </a>
@endpush

@section('content')

{{-- ── Stat Cards ── --}}
<div class="row g-3 mb-4">
    @foreach([
        ['Total Requests',  $stats['total'],   'fas fa-download', 'primary'],
        ['Today',           $stats['today'],   'fas fa-calendar-day',           'success'],
        ['Unique IPs',      $stats['ips'],     'fas fa-globe',                  'warning'],
        ['Total Files',     $stats['files'],   'fas fa-paperclip',              'info'],
    ] as [$label, $val, $icon, $color])
    <div class="col-6 col-md-3">
        <div class="card glass-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="small text-muted">{{ $label }}</span>
                <i class="{{ $icon }} text-{{ $color }}"></i>
            </div>
            <div class="fs-4 fw-bold">{{ number_format($val) }}</div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-3 mb-4">
    {{-- Trend chart --}}
    <div class="col-lg-7">
        <div class="card glass-card p-3 h-100">
            <div class="fw-semibold mb-3">
                <i class="fas fa-chart-bar text-primary me-2"></i>Requests (Last 14 Days)
            </div>
            <canvas id="trendChart" height="120"></canvas>
        </div>
    </div>

    {{-- Top content types --}}
    <div class="col-lg-5">
        <div class="card glass-card p-3 h-100">
            <div class="fw-semibold mb-3">
                <i class="fas fa-layer-group text-warning me-2"></i>Top Content Types
            </div>
            @forelse($stats['top_types'] as $type)
                <div class="mb-2">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-truncate" style="max-width:220px;">
                            {{ $type->content_type ?? 'unknown' }}
                        </span>
                        <span class="fw-bold text-primary ms-2">{{ number_format($type->total) }}</span>
                    </div>
                    <div class="progress" style="height:4px;">
                        <div class="progress-bar bg-primary"
                             style="width:{{ $stats['top_types']->first() ? round($type->total / $stats['top_types']->first()->total * 100) : 0 }}%">
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-muted small">No data yet.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- ── Endpoint & Examples ── --}}
<div class="card glass-card p-4 mb-4">
    <div class="fw-semibold mb-3">
        <i class="fas fa-satellite-dish text-primary me-2"></i>Public API Endpoint
        <span class="badge bg-success ms-2">Active</span>
    </div>

    <div class="input-group mb-3" style="max-width:600px;">
        <span class="input-group-text"><i class="fas fa-link"></i></span>
        <input type="text" class="form-control font-monospace fw-bold"
               id="endpointUrl" value="{{ $endpoint }}" readonly>
        <button class="btn btn-outline-secondary" onclick="copyText('endpointUrl', this)" title="Copy">
            <i class="fas fa-copy"></i>
        </button>
    </div>

    <div class="d-flex gap-2 flex-wrap mb-4">
        @foreach(['GET','POST','PUT','PATCH','DELETE','OPTIONS'] as $m)
            <span class="badge rounded-pill px-3 py-2
                @if($m==='GET') bg-primary
                @elseif($m==='POST') bg-success
                @elseif($m==='PUT') bg-warning text-dark
                @elseif($m==='PATCH') bg-info text-dark
                @elseif($m==='DELETE') bg-danger
                @else bg-secondary
                @endif">{{ $m }}</span>
        @endforeach
    </div>

    {{-- cURL tabs --}}
    <ul class="nav nav-tabs" id="curlTabs">
        @foreach($examples as $i => $ex)
            <li class="nav-item">
                <button class="nav-link {{ $i === 0 ? 'active' : '' }}"
                        data-bs-toggle="tab" data-bs-target="#curl-{{ $i }}">
                    <span class="badge me-1 {{ $ex['method'] === 'GET' ? 'bg-primary' : 'bg-success' }}">
                        {{ $ex['method'] }}
                    </span>{{ $ex['label'] }}
                </button>
            </li>
        @endforeach
    </ul>
    <div class="tab-content mt-2">
        @foreach($examples as $i => $ex)
            <div class="tab-pane fade {{ $i === 0 ? 'show active' : '' }}" id="curl-{{ $i }}">
                <div class="position-relative">
                    <pre class="rounded p-3 mb-2" id="curl-{{ $i }}-code"
                         style="background:#1e2a3a;color:#e2e8f0;font-size:.82rem;overflow-x:auto;">{{ $ex['curl'] }}</pre>
                    <button class="btn btn-sm btn-outline-light position-absolute top-0 end-0 m-2"
                            onclick="copyPre('curl-{{ $i }}-code', this)">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <div class="small text-muted">
                    <strong>Response:</strong>
                    <code>{"success":true,"log_id":"uuid","received_at":"..."}</code>
                </div>
            </div>
        @endforeach
    </div>
</div>

@endsection

@push('scripts')
<script>
const trendData = @json($stats['trend']);
const ctx = document.getElementById('trendChart')?.getContext('2d');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: trendData.map(r => r.date),
            datasets: [{ label: 'Requests', data: trendData.map(r => r.total),
                backgroundColor: 'rgba(13,110,253,.7)', borderRadius: 4 }]
        },
        options: { responsive: true, plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
}

function copyText(id, btn) {
    navigator.clipboard.writeText(document.getElementById(id).value)
        .then(() => { btn.innerHTML = '<i class="fas fa-check text-success"></i>'; setTimeout(() => btn.innerHTML = '<i class="fas fa-copy"></i>', 1500); });
}
function copyPre(id, btn) {
    navigator.clipboard.writeText(document.getElementById(id).innerText)
        .then(() => { btn.innerHTML = '<i class="fas fa-check"></i> Copied'; setTimeout(() => btn.innerHTML = '<i class="fas fa-copy"></i>', 1500); });
}
</script>
@endpush
