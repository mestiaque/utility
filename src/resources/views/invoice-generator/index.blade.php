@extends('me::master')
@section('title', 'Invoice Generator')
@push('buttons')
    <a href="{{ route('ut.invoice-generator.history') }}" class="btn btn-sm btn-encodex-show"><i class="fa fa-history"></i> History</a>
@endpush
@section('content')

<form method="POST" action="{{ route('ut.invoice-generator.generate') }}" enctype="multipart/form-data" target="_blank">
    @csrf

    <div class="card glass-cardX mb-3">
        <div class="card-header fw-bold">Shop Details</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Shop Name</label>
                    <input type="text" name="shop_name" class="form-control" value="{{ old('shop_name') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Logo</label>
                    <input type="file" name="logo" accept="image/*" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input type="text" name="shop_phone" class="form-control" value="{{ old('shop_phone') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Address</label>
                    <input type="text" name="shop_address" class="form-control" value="{{ old('shop_address') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="shop_email" class="form-control" value="{{ old('shop_email') }}">
                </div>
            </div>
        </div>
    </div>

    <div class="card glass-cardX mb-3">
        <div class="card-header fw-bold">Invoice Details</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Invoice Number</label>
                    <input type="text" name="invoice_number" class="form-control" placeholder="Auto-generated if left blank">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Invoice Date</label>
                    <input type="date" name="invoice_date" class="form-control" value="{{ date('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Customer Name</label>
                    <input type="text" name="customer_name" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Customer Phone</label>
                    <input type="text" name="customer_phone" class="form-control">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Customer Address</label>
                    <input type="text" name="customer_address" class="form-control">
                </div>
            </div>
        </div>
    </div>

    <div class="card glass-cardX mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-bold">Items</span>
            <button type="button" id="addItemBtn" class="btn btn-sm btn-encodex-create"><i class="fa fa-plus"></i> Add Item</button>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-sm" id="itemsTable">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="width:100px">Qty</th>
                        <th style="width:140px">Unit Price</th>
                        <th style="width:140px">Warranty</th>
                        <th style="width:40px"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="text" name="description[]" class="form-control" required></td>
                        <td><input type="number" name="qty[]" class="form-control" value="1" min="0" step="any"></td>
                        <td><input type="number" name="unit_price[]" class="form-control" value="0" min="0" step="any"></td>
                        <td><input type="text" name="warranty[]" class="form-control" placeholder="e.g. 12 Months"></td>
                        <td class="text-center"><button type="button" class="btn btn-sm btn-encodex-delete removeItemBtn"><i class="fa fa-trash"></i></button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card glass-cardX mb-3">
        <div class="card-header fw-bold">Terms &amp; Conditions</div>
        <div class="card-body">
            <textarea name="terms" class="form-control" rows="4">1. Warranty as per manufacturer's policy.
2. Products once sold cannot be returned/exchanged after 24 hours without original invoice.
3. Please check items before leaving the counter.</textarea>
        </div>
    </div>

    <button type="submit" class="btn btn-encodex-create"><i class="fa fa-file-invoice"></i> Generate Invoice</button>
</form>

@endsection

@push('js')
<script>
document.getElementById('addItemBtn').addEventListener('click', function () {
    const tbody = document.querySelector('#itemsTable tbody');
    const row = tbody.rows[0].cloneNode(true);
    row.querySelectorAll('input').forEach(function (input) {
        if (input.name === 'qty[]') { input.value = 1; }
        else if (input.name === 'unit_price[]') { input.value = 0; }
        else { input.value = ''; }
    });
    tbody.appendChild(row);
});

document.getElementById('itemsTable').addEventListener('click', function (e) {
    const btn = e.target.closest('.removeItemBtn');
    if (!btn) return;
    const tbody = document.querySelector('#itemsTable tbody');
    if (tbody.rows.length > 1) {
        btn.closest('tr').remove();
    }
});
</script>
@endpush
