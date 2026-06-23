@extends('me::master')

@section('title', 'Image Share')

@push('buttons')
    <button class="btn btn-sm btn-encodex-create" data-bs-toggle="modal" data-bs-target="#uploadModal">
        <i class="fas fa-upload me-1"></i> Upload Image
    </button>
@endpush

@section('content')

{{-- ── Flash --}}
@if(session('img_success'))
    <div class="alert alert-success alert-dismissible fade show py-2 mb-3" role="alert">
        <i class="fas fa-check-circle me-1"></i>{{ session('img_success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ── Stats row --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card glass-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="small text-muted">Total Images</span>
                <i class="fas fa-images text-primary"></i>
            </div>
            <div class="fs-4 fw-bold">{{ $images->total() }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card glass-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="small text-muted">This Page</span>
                <i class="fas fa-layer-group text-success"></i>
            </div>
            <div class="fs-4 fw-bold">{{ $images->count() }}</div>
        </div>
    </div>
</div>

{{-- ── Gallery --}}
<div class="card glass-card">
    <div class="card-body p-4">

        @if($images->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="fas fa-images fa-3x mb-3 opacity-25"></i>
                <p class="mb-2">No images uploaded yet.</p>
                <button class="btn btn-sm btn-encodex-create" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="fas fa-upload me-1"></i> Upload your first image
                </button>
            </div>
        @else
            <div class="row g-3">
                @foreach($images as $image)
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="card border-0 shadow-sm h-100" style="border-radius:10px;overflow:hidden;">

                            {{-- Thumbnail --}}
                            <a href="{{ route('ut.image-share.public', $image->uuid) }}" target="_blank">
                                <img
                                    src="{{ route('ut.image-share.public', $image->uuid) }}"
                                    alt="{{ $image->original_name }}"
                                    class="w-100"
                                    style="height:160px;object-fit:cover;"
                                    loading="lazy"
                                >
                            </a>

                            <div class="card-body p-2">
                                {{-- Name + size --}}
                                <p class="mb-0 small text-truncate fw-semibold" title="{{ $image->original_name }}">
                                    {{ $image->original_name }}
                                </p>
                                <p class="mb-2 small text-muted">
                                    {{ $image->formatted_size }}
                                    &middot; {{ $image->created_at->diffForHumans() }}
                                </p>

                                {{-- Copy URL --}}
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary w-100 mb-1"
                                    onclick="copyPublicUrl(this, '{{ $image->public_url }}')"
                                >
                                    <i class="fas fa-copy me-1"></i> Copy URL
                                </button>

                                {{-- Delete --}}
                                <form
                                    method="POST"
                                    action="{{ route('ut.image-share.destroy', $image->uuid) }}"
                                    onsubmit="return confirm('Delete this image? This cannot be undone.')"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-encodex-delete w-100">
                                        <i class="fas fa-trash me-1"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $images->links() }}
            </div>
        @endif

    </div>
</div>

{{-- ── Upload Modal --}}
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel">
    <div class="modal-dialog glass-card">
        <form
            class="modal-content"
            method="POST"
            action="{{ route('ut.image-share.store') }}"
            enctype="multipart/form-data"
        >
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalLabel">
                    <i class="fas fa-upload me-2 text-primary"></i>Upload Image
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                @if($errors->any())
                    <div class="alert alert-danger py-2 mb-3">
                        @foreach($errors->all() as $error)
                            <div><i class="fas fa-exclamation-circle me-1"></i>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label fw-semibold">Choose Image</label>
                    <input
                        type="file"
                        name="image"
                        id="imageInput"
                        accept="image/jpeg,image/png,image/webp"
                        class="form-control"
                        required
                        onchange="previewImage(this)"
                    >
                    <div class="form-text">JPG, PNG, WEBP — max 5 MB</div>
                </div>

                {{-- Preview --}}
                <div id="previewWrap" class="d-none text-center mb-2">
                    <img id="previewImg" src="" alt="preview"
                         class="rounded border"
                         style="max-height:200px;max-width:100%;object-fit:contain;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i class="fas fa-upload me-1"></i> Upload
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function previewImage(input) {
    const wrap = document.getElementById('previewWrap');
    const img  = document.getElementById('previewImg');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { img.src = e.target.result; wrap.classList.remove('d-none'); };
        reader.readAsDataURL(input.files[0]);
    }
}

function copyPublicUrl(btn, url) {
    navigator.clipboard.writeText(url).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check me-1 text-success"></i> Copied!';
        setTimeout(() => btn.innerHTML = orig, 2000);
    }).catch(() => prompt('Copy this URL:', url));
}

@if($errors->any())
    // Re-open upload modal if validation failed
    document.addEventListener('DOMContentLoaded', () => {
        new bootstrap.Modal(document.getElementById('uploadModal')).show();
    });
@endif
</script>
@endpush
