@extends('me::master')

@section('title', 'Nested Folders ZIP')

@section('content')
<div class="card glass-card w-100 p-4 shadow-sm">
    <h3 class="mb-3">Download Nested Folder ZIP</h3>

    <p class="text-muted mb-4">
        Generate a ZIP file containing recursively nested folders.
        Just set a base name, depth level, and folder count per level.
    </p>

    <form id="nestedForm" class="mb-4">
        @csrf

        <div class="mb-3">
            <label for="baseName" class="form-label fw-semibold">Base Folder Name</label>
            <!-- name="baseName" যোগ করা হয়েছে -->
            <input type="text" name="baseName" id="baseName" class="form-control" placeholder="e.g. project" required>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="depth" class="form-label fw-semibold">Depth</label>
                <!-- name="depth" যোগ করা হয়েছে -->
                <input type="number" name="depth" id="depth" class="form-control" placeholder="e.g. 3" required>
            </div>

            <div class="col-md-6 mb-3">
                <label for="count" class="form-label fw-semibold">Count per Level</label>
                <!-- name="count" যোগ করা হয়েছে -->
                <input type="number" name="count" id="count" class="form-control" placeholder="e.g. 2" required>
            </div>
        </div>

        <button type="submit" id="nestedBtn" class="btn btn-primary w-100">
            Generate ZIP
        </button>
    </form>


    <div id="nestedInfo" class="border rounded p-3 bg-light text-muted"
        style="height:200px; overflow-y:auto;">
        Waiting...
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('nestedForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const btn = document.getElementById('nestedBtn');
    const info = document.getElementById('nestedInfo');
    const formData = new FormData(this);

    btn.disabled = true;
    info.innerHTML = "Preparing ZIP...";

    try {
        const response = await fetch("{{ route('ut.generateNestedFolder') }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('input[name=_token]').value
            },
            body: formData,
        });

        if (!response.ok) throw new Error("Failed to generate ZIP");

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;

        // ফাইলনেম ইনপুট থেকে নেওয়া
        const fileName = (formData.get('baseName') || 'folder') + "_nested.zip";
        a.download = fileName;

        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        a.remove();

        info.innerHTML += "<br><strong class='text-success'>ZIP successfully downloaded!</strong>";
    } catch (err) {
        info.innerHTML += "<br><span class='text-danger'>Error: " + err.message + "</span>";
    } finally {
        btn.disabled = false;
    }
});
</script>
@endpush
