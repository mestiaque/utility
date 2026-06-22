@extends('me::master')

@section('title', 'Data Receiver — Files')

@push('buttons')
    <a href="{{ route('ut.data-receiver') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
@endpush

@section('content')

{{-- Filters --}}
<div class="card glass-card p-3 mb-3">
    <form method="GET" action="{{ route('ut.data-receiver.files') }}" class="row g-2 align-items-end">
        <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold mb-1">Extension</label>
            <input type="text" name="extension" class="form-control form-control-sm"
                   placeholder="pdf, jpg, zip…" value="{{ request('extension') }}">
        </div>
        <div class="col-6 col-md-5">
            <label class="form-label small fw-semibold mb-1">Filename</label>
            <input type="text" name="filename" class="form-control form-control-sm"
                   placeholder="Search filename…" value="{{ request('filename') }}">
        </div>
        <div class="col-12 col-md-4 d-flex gap-2 align-items-end">
            <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                <i class="fas fa-search me-1"></i> Filter
            </button>
            <a href="{{ route('ut.data-receiver.files') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>
</div>

{{-- Grid --}}
@forelse($files as $file)
    @if($loop->first) <div class="row g-3"> @endif

    <div class="col-6 col-md-4 col-lg-3">
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
                    <div class="d-flex align-items-center justify-content-center h-100 bg-secondary bg-opacity-25">
                        <i class="fas fa-file text-secondary fa-3x"></i>
                    </div>
                @endif
            </div>
            <div class="p-2">
                <p class="small fw-semibold mb-1 text-truncate" title="{{ $file->original_name }}">
                    {{ $file->original_name }}
                </p>
                <div class="d-flex gap-1 mb-1 flex-wrap">
                    <span class="badge bg-secondary" style="font-size:.65rem;">{{ strtoupper($file->extension) }}</span>
                    <span class="badge bg-light text-dark border" style="font-size:.65rem;">{{ $file->formatted_size }}</span>
                </div>
                <div class="text-muted" style="font-size:.7rem;">{{ $file->created_at?->diffForHumans() }}</div>

                <div class="d-flex gap-1 mt-2">
                    @if($file->dataLog)
                        <a href="{{ route('ut.data-receiver.log.show', $file->dataLog->uuid) }}"
                           class="btn btn-sm btn-outline-primary flex-grow-1" title="View log">
                            <i class="fas fa-eye"></i>
                        </a>
                    @endif
                    <a href="{{ route('ut.data-receiver.file.download', $file->uuid) }}"
                       class="btn btn-sm btn-outline-success" title="Download">
                        <i class="fas fa-download"></i>
                    </a>
                    <form method="POST" action="{{ route('ut.data-receiver.file.destroy', $file->uuid) }}"
                          onsubmit="return confirm('Delete this file?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if($loop->last) </div> @endif

@empty
    <div class="text-center py-5 text-muted">
        <i class="fas fa-folder-open fa-3x d-block mb-3"></i>
        No files found.
    </div>
@endforelse

@if($files->hasPages())
    <div class="mt-3">{{ $files->links() }}</div>
@endif

@endsection
