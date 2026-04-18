@extends('me::printMaster2')
@section('title', 'Bajar List Print')
@section('contents')
<h3 class="text-center mb-4 text-primary">
    {{ $group->title }} - {{ formatDate($group->group_date) }}
</h3>
<table class="table table-bordered table-sm mt-3 table-encondex">
    <thead>
        <tr>
            <th>@if(app()->getLocale() === 'bn') আইটেমের নাম @else Item Name @endif</th>
            <th>@if(app()->getLocale() === 'bn') ব্র্যান্ড @else Brand @endif</th>
            <th>@if(app()->getLocale() === 'bn') উৎস @else Source @endif</th>
            <th>@if(app()->getLocale() === 'bn') মূল্য @else Price @endif</th>
            <th>@if(app()->getLocale() === 'bn') বিবরণ @else Description @endif</th>
            {{-- <th>Status</th> --}}
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
        <tr>
            <td>{{ $item->item_name }}</td>
            <td>{{ $item->brand }}</td>
            <td>{{ $item->source }}</td>
            <td class="text-right">
                @if($item->price > 0)
                    ৳ {{ toBanglaNumber($item->price, 2) }}
                @endif
            </td>
            <td>{{ $item->description }}</td>
            {{-- <td>{{ ucfirst($item->status) }}</td> --}}
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr >
            <th colspan="3" class="text-right" style="background: rgb(243, 239, 239) !important">@if(app()->getLocale() === 'bn') মোট মূল্য @else Total Amount @endif</th>
            <th colspan="1" class="text-right" style="background: rgb(243, 239, 239) !important">৳ {{ toBanglaNumber($group->total_amount ?? 0, 2) }}</th>
            <th style="background: rgb(243, 239, 239) !important"></th>
        </tr>
    </tfoot>
</table>
@endsection

@push('css')
<style>
    table td, table th {
        font-size: 10px !important;
        padding: 0.25rem !important;
    }
</style>
@endpush
