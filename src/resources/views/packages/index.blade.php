@extends('me::master')

@section('title', trans('Package Generator'))

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <!-- Main Card -->
            <div class="card glass-card border-0 shadow-lg rounded-4 overflow-hidden">

                <div class="card-body p-4">
                    {{-- <p class="text-muted small mb-4">নিচের বক্সে আপনার প্যাকেজের নাম লিখুন এবং জেনারেট বাটনে ক্লিক করুন।</p> --}}

                    <!-- Input Group -->
                    <div class="form-floating mb-4">
                        <input type="text" class="form-control" id="packageName" placeholder="e.g. MyAwesomePlugin" autofocus autocomplete="off">
                        <label for="packageName">Package Name</label>
                    </div>

                    <!-- Action Button -->
                    <div class="d-grid">
                        <button id="generateBtn" class="btn btn-encodex-save btn-lg rounded-3 py-2">
                            <i class="fas fa-magic me-2"></i> Generate & Download
                        </button>
                    </div>

                    <!-- Status Section (Initially Hidden) -->
                    <div id="statusSection" class="mt-4 d-none">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="small fw-bold text-primary" id="statusText">Processing...</span>
                            <span class="small text-muted" id="progressPercent">0%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%"></div>
                        </div>

                        <!-- Log Terminal -->
                        <div id="generatorInfo" class="mt-3 p-3 bg-light rounded-3 small text-monospace border" style="height: 120px; overflow-y: auto; font-family: 'Courier New', Courier, monospace;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@include('utility::ext.aquarium')
@endsection

@push('scripts')
    <script>
        document.getElementById('generateBtn').addEventListener('click', async function() {
            const btn = this;
            const nameInput = document.getElementById('packageName');
            const packageName = nameInput.value.trim();
            const statusSection = document.getElementById('statusSection');
            const progressBar = document.getElementById('progressBar');
            const progressPercent = document.getElementById('progressPercent');
            const info = document.getElementById('generatorInfo');
            const statusText = document.getElementById('statusText');

            if (!packageName) {
                nameInput.classList.add('is-invalid');
                return;
            }
            nameInput.classList.remove('is-invalid');

            // UI Reset & Show
            btn.disabled = true;
            statusSection.classList.remove('d-none');
            progressBar.style.width = '0%';
            info.innerHTML = '<span class="text-primary">Initializing engine...</span><br>';

            try {
                const response = await fetch("{{ route('ut.generate-package') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
                    body: JSON.stringify({ name: packageName })
                });

                if (!response.ok) throw new Error('Generation failed');

                const blob = await response.blob();

                // Faster & Smoother Progress Animation
                let progress = 0;
                const interval = setInterval(() => {
                    progress += 5;
                    progressBar.style.width = progress + '%';
                    progressPercent.innerText = progress + '%';

                    if(progress === 30) info.innerHTML += 'Creating file structure...<br>';
                    if(progress === 60) info.innerHTML += 'Compiling dependencies...<br>';
                    if(progress === 90) info.innerHTML += 'Zipping package...<br>';

                    if(progress >= 100) {
                        clearInterval(interval);
                        statusText.innerText = 'Completed!';
                        info.innerHTML += '<strong class="text-success">Done! Check your downloads.</strong>';

                        // Trigger Download
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `${packageName}.zip`;
                        document.body.appendChild(a);
                        a.click();
                        a.remove();

                        btn.disabled = false;
                    }
                }, 50);

            } catch (error) {
                info.innerHTML += `<span class="text-danger">Error: ${error.message}</span>`;
                btn.disabled = false;
            }
        });
    </script>
@endpush
