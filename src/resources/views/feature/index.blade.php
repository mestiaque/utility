@extends('me::master')

@section('title', 'Advanced Feature Generator')

@section('content')
<div class="card glass-card w-100 p-4">
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <label class="form-label fw-boldx mb-1">Feature Name (StudlyCase)</label>
            <input type="text" id="featureName" class="form-control" value="Post" placeholder="e.g. BlogPost">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-boldx mb-1">Base Button Class</label>
            <input type="text" id="btnBase" class="form-control" value="btn-encodex">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-boldx mb-1">Table Class</label>
            <input type="text" id="tableClass" class="form-control" value="table-encodex">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-boldx mb-1">Route Prefix</label>
            <input type="text" id="routePrefix" class="form-control" value="me">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-encodex table-bordered align-middle" id="columnsTable">
            <thead class="table-light">
                <tr class="text-center">
                    <th>Column Name</th>
                    <th>Type</th>
                    <th>Length</th>
                    <th class="text-center">Nullable</th>
                    <th>Default</th>
                    <th class="text-center"><button id="addRow" class="btn btn-encodex-create btn-sm" style="color:white !important;    border-color: white !important;"><i class="fas fa-plus"></i></button></th>
                </tr>
            </thead>
            <tbody id="columnBody">
                <tr>
                    <td><input type="text" class="form-control form-control-sm col-name" value="title"></td>
                    <td>
                        <select class="form-select form-select-sm col-type">
                            <option value="string">string</option>
                            <option value="text">text</option>
                            <option value="mediumtext">mediumtext</option>
                            <option value="longtext">longtext</option>
                            <option value="integer">integer</option>
                            <option value="biginteger">biginteger</option>
                            <option value="tinyinteger">tinyinteger</option>
                            <option value="smallinteger">smallinteger</option>
                            <option value="boolean">boolean</option>
                            <option value="decimal">decimal</option>
                            <option value="float">float</option>
                            <option value="double">double</option>
                            <option value="date">date</option>
                            <option value="datetime">datetime</option>
                            <option value="timestamp">timestamp</option>
                            <option value="time">time</option>
                            <option value="json">json</option>
                            <option value="char">char</option>
                        </select>
                    </td>
                    <td><input type="number" class="form-control form-control-sm col-len" value="255"></td>
                    <td class="text-center"><input type="checkbox" class="form-check-input col-null"></td>
                    <td><input type="text" class="form-control form-control-sm col-def"></td>
                    <td class="text-center"><button class="btn btn-sm btn-encodex-cancel btn-sm remove-row"><i class="fas fa-times"></i></button></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="text-end mt-3">
        {{-- <button id="addRow" class="btn btn-encodex-create btn-sm"><i class="fas fa-plus me-1"></i>Add Column</button> --}}
        <button id="generateBtn" class="btn  btn-encodex-save px-4 float-right"><i class="fas fa-download me-1"></i>Generate & Download</button>
    </div>
</div>

@push('scripts')
<script>
    const typeOptions = `
        <option value="string">string</option>
        <option value="text">text</option>
        <option value="mediumtext">mediumtext</option>
        <option value="longtext">longtext</option>
        <option value="integer">integer</option>
        <option value="biginteger">biginteger</option>
        <option value="tinyinteger">tinyinteger</option>
        <option value="smallinteger">smallinteger</option>
        <option value="boolean">boolean</option>
        <option value="decimal">decimal</option>
        <option value="float">float</option>
        <option value="double">double</option>
        <option value="date">date</option>
        <option value="datetime">datetime</option>
        <option value="timestamp">timestamp</option>
        <option value="time">time</option>
        <option value="json">json</option>
        <option value="char">char</option>
    `;

    // Add new row
    document.getElementById('addRow').addEventListener('click', () => {
        const row = `<tr>
            <td><input type="text" class="form-control form-control-sm col-name"></td>
            <td><select class="form-select form-control-sm col-type">${typeOptions}</select></td>
            <td><input type="number" class="form-control form-control-sm col-len" value="255"></td>
            <td class="text-center"><input type="checkbox" class="form-check-input col-null"></td>
            <td><input type="text" class="form-control form-control-sm col-def"></td>
            <td class="text-center"><button class="btn btn-encodex-cancel btn-sm remove-row"><i class="fas fa-times"></i></button></td>
        </tr>`;
        document.getElementById('columnBody').insertAdjacentHTML('beforeend', row);
    });

    // Remove row
    document.addEventListener('click', e => {
        if(e.target.closest('.remove-row')) e.target.closest('tr').remove();
    });

    // Generate feature
    document.getElementById('generateBtn').addEventListener('click', function() {
        const columns = Array.from(document.querySelectorAll('#columnBody tr')).map(tr => ({
            name: tr.querySelector('.col-name').value,
            type: tr.querySelector('.col-type').value,
            length: tr.querySelector('.col-len').value,
            is_null: tr.querySelector('.col-null').checked,
            default: tr.querySelector('.col-def').value
        }));

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Processing...';

        fetch("{{ route('ut.generateLaravelFeature') }}", {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': "{{ csrf_token() }}", 'Content-Type': 'application/json'},
            body: JSON.stringify({
                name: document.getElementById('featureName').value,
                btn_base: document.getElementById('btnBase').value,
                table_class: document.getElementById('tableClass').value,
                route_prefix: document.getElementById('routePrefix').value,
                columns: columns
            })
        })
        .then(r => { if(!r.ok) throw new Error('Server Error'); return r.blob(); })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = document.getElementById('featureName').value + '_feature.zip';
            document.body.appendChild(a);
            a.click();
            a.remove();
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-download me-1"></i>Generate Flat ZIP';
        })
        .catch(err => {
            alert(err.message);
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-download me-1"></i>Generate Flat ZIP';
        });
    });
</script>
@endpush
@endsection
