@extends('me::master')

@section('title', 'ADMS Settings')

@section('content')

{{-- ── Flash messages ─────────────────────────────────────────────────────── --}}
@if(session('config_success'))
    <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('config_success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('schedule_success'))
    <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('schedule_success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4 mb-4">

    {{-- ── LEFT: Laravel API Configuration ──────────────────────────────────── --}}
    <div class="col-lg-6">
        <div class="card glass-card h-100">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-4">
                    <i class="fas fa-plug text-primary me-2"></i>Laravel API Configuration
                </h6>

                <form method="POST" action="{{ route('ut.adms.config.save') }}" id="configForm">
                    @csrf

                    {{-- API Base URL --}}
                    <div class="mb-3">
                        <label class="form-label small text-muted">Laravel API Base URL</label>
                        <input
                            type="url"
                            name="api_base_url"
                            class="form-control @error('api_base_url') is-invalid @enderror"
                            value="{{ old('api_base_url', $settings->api_base_url) }}"
                            placeholder="https://mestiaque.com/api/receive"
                        >
                        <div class="form-text">Include /api — e.g. https://myapp.com/api</div>
                        @error('api_base_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- API Token --}}
                    <div class="mb-3">
                        <label class="form-label small text-muted">API Token (Bearer) — optional</label>
                        <input
                            type="text"
                            name="api_token"
                            class="form-control @error('api_token') is-invalid @enderror"
                            value="{{ old('api_token', $settings->api_token) }}"
                            placeholder="Leave empty if not required"
                        >
                        <div class="form-text">Laravel Sanctum / Passport token (leave blank if public route)</div>
                        @error('api_token')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Fetch Employee URL --}}
                    <div class="mb-3">
                        <label class="form-label small text-muted">Fetch Employee URL</label>
                        <input
                            type="url"
                            name="fetch_employee_url"
                            class="form-control @error('fetch_employee_url') is-invalid @enderror"
                            value="{{ old('fetch_employee_url', $settings->fetch_employee_url) }}"
                            placeholder="https://yourapp.com/fetch-employee"
                        >
                        <div class="form-text">GET — bridge will call this to get employee list</div>
                        @error('fetch_employee_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Sync Attendance URL --}}
                    <div class="mb-3">
                        <label class="form-label small text-muted">Sync Attendance URL</label>
                        <input
                            type="url"
                            name="sync_attendance_url"
                            class="form-control @error('sync_attendance_url') is-invalid @enderror"
                            value="{{ old('sync_attendance_url', $settings->sync_attendance_url) }}"
                            placeholder="https://mestiaque.com/api/receive"
                        >
                        <div class="form-text">POST — bridge will push attendance records here</div>
                        @error('sync_attendance_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="fas fa-save me-1"></i> Save Config
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm px-4" id="testConnectionBtn">
                            <i class="fas fa-wifi me-1"></i> Test Connection
                        </button>
                    </div>

                    {{-- Test connection result --}}
                    <div id="testResult" class="mt-3 d-none small"></div>
                </form>

            </div>
        </div>
    </div>

    {{-- ── RIGHT: Sync Schedule ───────────────────────────────────────────────── --}}
    <div class="col-lg-6">
        <div class="card glass-card">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-4">
                    <i class="fas fa-clock text-warning me-2"></i>Sync Schedule
                </h6>

                <form method="POST" action="{{ route('ut.adms.schedule.save') }}">
                    @csrf

                    {{-- Employee Sync Cron --}}
                    <div class="mb-4">
                        <label class="form-label small text-muted">Employee Sync (Cron)</label>
                        <input
                            type="text"
                            name="employee_sync_cron"
                            id="employeeCron"
                            class="form-control font-monospace @error('employee_sync_cron') is-invalid @enderror"
                            value="{{ old('employee_sync_cron', $settings->employee_sync_cron) }}"
                            placeholder="0 * * * *"
                        >
                        <div class="form-text">How often to sync employees to devices</div>
                        @error('employee_sync_cron')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Attendance Fetch Cron --}}
                    <div class="mb-3">
                        <label class="form-label small text-muted">Attendance Fetch (Cron)</label>
                        <input
                            type="text"
                            name="attendance_fetch_cron"
                            id="attendanceCron"
                            class="form-control font-monospace @error('attendance_fetch_cron') is-invalid @enderror"
                            value="{{ old('attendance_fetch_cron', $settings->attendance_fetch_cron) }}"
                            placeholder="*/15 * * * *"
                        >
                        <div class="form-text">How often to pull attendance from TCP devices</div>
                        @error('attendance_fetch_cron')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Quick-select buttons --}}
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        @foreach(['Every 5 min' => '*/5 * * * *', 'Every 15 min' => '*/15 * * * *', 'Every 30 min' => '*/30 * * * *', 'Hourly' => '0 * * * *'] as $label => $cron)
                            <button
                                type="button"
                                class="btn btn-outline-secondary btn-sm"
                                onclick="document.getElementById('attendanceCron').value='{{ $cron }}'"
                            >{{ $label }}</button>
                        @endforeach
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        <i class="fas fa-save me-1"></i> Save Schedule
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

{{-- ── ADMS Server Info ─────────────────────────────────────────────────────── --}}
<div class="card glass-card">
    <div class="card-body p-4">
        <h6 class="fw-semibold mb-4">
            <i class="fas fa-server text-info me-2"></i>ADMS Server Info
        </h6>

        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded">
                    <div class="small text-muted mb-1">ADMS Port</div>
                    <div class="fw-bold fs-5">{{ $settings->adms_port }}</div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded">
                    <div class="small text-muted mb-1">Status</div>
                    @if($isRunning)
                        <div class="fw-bold text-success">
                            <span class="me-1">●</span>Running
                        </div>
                    @else
                        <div class="fw-bold text-danger">
                            <span class="me-1">●</span>Stopped
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded">
                    <div class="small text-muted mb-1">Protocol</div>
                    <div class="fw-bold">{{ $settings->adms_protocol }}</div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded">
                    <div class="small text-muted mb-1">Device URL</div>
                    <div class="fw-bold text-truncate" style="font-size:.9rem;" title="{{ $deviceUrl }}">
                        {{ $deviceUrl }}
                    </div>
                </div>
            </div>
        </div>

        <p class="text-muted small mt-3 mb-0">
            Change <code>ADMS_PORT</code> in .env file and restart the server.
        </p>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.getElementById('testConnectionBtn').addEventListener('click', function () {
    const btn    = this;
    const result = document.getElementById('testResult');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Testing…';
    result.className = 'mt-3 small';
    result.innerHTML = '';

    fetch('{{ route('ut.adms.test-connection') }}', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        result.classList.remove('d-none');
        if (data.ok) {
            result.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>' + data.message + '</span>';
        } else {
            result.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>' + data.message + '</span>';
        }
    })
    .catch(() => {
        result.classList.remove('d-none');
        result.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Request failed.</span>';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-wifi me-1"></i> Test Connection';
    });
});
</script>
@endpush
