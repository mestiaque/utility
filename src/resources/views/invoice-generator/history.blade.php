@extends('me::master')
@section('title', 'Invoice History')
@push('buttons')
    <a href="{{ route('ut.invoice-generator.index') }}" class="btn btn-sm btn-encodex-create"><i class="fa fa-plus"></i> New Invoice</a>
@endpush
@section('content')

<div class="card glass-cardX mb-3">
    <div class="card-body">
        <form method="GET" class="mb-3">
            <div class="row">
                <div class="col-md-4">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search invoice # or customer..." class="form-control form-control-sm">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-encodex-search btn-sm me-1"><i class="fa fa-search"></i> Search</button>
                    <a href="{{ route('ut.invoice-generator.history') }}" class="btn btn-encodex-clear btn-sm"><i class="fa fa-eraser"></i> Reset</a>
                </div>
            </div>
        </form>

        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th class="text-right">Grand Total</th>
                    <th style="width:120px"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                <tr>
                    <td>{{ $invoice['invoice_number'] }}</td>
                    <td>{{ $invoice['invoice_date'] }}</td>
                    <td>{{ $invoice['customer_name'] ?: 'Walk-in Customer' }}</td>
                    <td class="text-right">{{ number_format($invoice['grand_total'], 2) }}</td>
                    <td class="text-center">
                        <a href="{{ route('ut.invoice-generator.print', $invoice['invoice_number']) }}" target="_blank" class="btn btn-sm btn-encodex-print" title="Print"><i class="fa fa-print"></i></a>
                        <form action="{{ route('ut.invoice-generator.destroy', $invoice['invoice_number']) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-encodex-delete" onclick="return confirm('Delete this invoice?')" title="Delete"><i class="fa fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted">No invoices saved yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
