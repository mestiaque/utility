@extends('me::printMaster2')
@section('title', 'Bajar List Print')
@section('contents')
<h3 class="text-center mb-4 text-primary">
    {{ $group->title }} - {{ formatDate($group->group_date) }}
</h3>
<table class="table table-bordered table-sm mt-3 table-encondex">
    <thead>
        <tr>
            <th>Item Name</th>
            <th>Brand</th>
            <th>Source</th>
            <th>Price</th>
            <th>Description</th>
            {{-- <th>Status</th> --}}
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
        <tr>
            <td>{{ $item->item_name }}</td>
            <td>{{ $item->brand }}</td>
            <td>{{ $item->source }}</td>
            <td class="text-right">৳{{ number_format($item->price, 2) }}</td>
            <td>{{ $item->description }}</td>
            {{-- <td>{{ ucfirst($item->status) }}</td> --}}
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr >
            <th colspan="3" class="text-right" style="background: rgb(243, 239, 239) !important">Total Amount:</th>
            <th colspan="1" class="text-right" style="background: rgb(243, 239, 239) !important">৳ {{ number_format($group->total_amount ?? 0, 2) }}</th>
            <th style="background: rgb(243, 239, 239) !important"></th>
        </tr>
    </tfoot>
</table>
@endsection
