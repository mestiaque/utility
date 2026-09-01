@extends('me::printMaster2')
@section('title', $invoice_number)
@section('contents')

<div class="print-header">
    <div class="header-row">
        <div class="header-logo">
            @if($logo)
                <img src="{{ $logo }}" class="company-logo">
            @endif
        </div>
        <div class="header-company">
            <div class="company-name">{{ $shop_name }}</div>
            @if($shop_address)
                <div class="company-address">{{ $shop_address }}</div>
            @endif
            <div class="company-contact">
                @if($shop_phone) Phone: {{ $shop_phone }} @endif
                @if($shop_phone && $shop_email) | @endif
                @if($shop_email) Email: {{ $shop_email }} @endif
            </div>
        </div>
        <div class="header-barcode">
            <svg id="invoiceBarcode"></svg>
        </div>
    </div>
    <div class="report-title"><span>INVOICE</span></div>
</div>

<table>
    <tr>
        <td style="width:50%">
            <strong>Bill To</strong><br>
            {{ $customer_name ?: 'Walk-in Customer' }}<br>
            @if($customer_phone) {{ $customer_phone }}<br> @endif
            @if($customer_address) {{ $customer_address }} @endif
        </td>
        <td class="text-right">
            <strong>Invoice #:</strong> {{ $invoice_number }}<br>
            <strong>Date:</strong> {{ $invoice_date->format('d M Y') }}<br>
            <span class="print-time">Printed at: {{ now()->format('d M Y H:i') }}</span>
        </td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th style="width: 2%">SL</th>
            <th style="width: 58%">Description</th>
            <th style="width: 10%" class="text-right">Qty</th>
            <th style="width: 10%" class="text-right">Unit Price</th>
            <th style="width: 10%">Warranty</th>
            <th style="width: 10%" class="text-right">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $item['description'] }}</td>
            <td class="text-right">{{ rtrim(rtrim(number_format($item['qty'], 2), '0'), '.') }}</td>
            <td class="text-right">{{ number_format($item['unit_price'], 2) }}</td>
            <td>{{ $item['warranty'] ?? '-' }}</td>
            <td class="text-right">{{ number_format($item['amount'], 2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="grandtotal-row">
            <td colspan="5" class="text-right">Grand Total</td>
            <td class="text-right">{{ number_format($grandTotal, 2) }}</td>
        </tr>
    </tfoot>
</table>

@if($terms)
<div class="terms-box">
    <strong>Terms &amp; Conditions</strong>
    <div>{{ $terms }}</div>
</div>
@endif

<div class="invoice-bottom">
    <div class="print-footer">
        <div class="signature-box">
            <div class="signature-line">Customer Signature</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">Authorized Signature &amp; Seal</div>
        </div>
    </div>
    <div class="computer-generated-note">This is a computer generated invoice and does not require a physical signature or seal.</div>
</div>

@endsection

@push('css')
<style>
    .print-header .header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 5px;
    }
    .print-header .header-logo,
    .print-header .header-barcode {
        flex-basis: 150px;
        width: 150px;
        display: flex;
        justify-content: center;
        flex-shrink: 0;
    }
    .print-header .company-logo {
        max-width: 70px;
        max-height: 70px;
        object-fit: contain;
    }
    .print-header .header-company {
        flex: 1 1 auto;
        text-align: center;
        min-width: 0;
    }
    .print-header #invoiceBarcode {
        max-width: 150px;
    }
    .terms-box {
        margin-top: 15px;
        font-size: 11px;
        color: #444;
    }
    .terms-box div {
        white-space: pre-line;
    }
    .container{
        width: 210mm;
        height: 297mm;
        box-shadow: 0 0 8px #444;
        display: flex;
        flex-direction: column;
    }
    .print-time {
        float: none !important;
        margin-top: 0 !important;
        display: inline-block;
    }
    .invoice-bottom {
        margin-top: auto;
    }
    .print-footer { margin-bottom: 1rem !important}
    .computer-generated-note {
        text-align: center;
        font-size: 10px;
        color: #777;
        margin-top: 10px;
    }
    @media print {

        .container {
            box-shadow: none;
        }
    }
</style>
@endpush

@push('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>
<script>
    JsBarcode("#invoiceBarcode", @json($invoice_number), {
        format: "CODE128",
        width: 1.5,
        height: 40,
        fontSize: 12,
        margin: 0
    });
</script>
@endpush
