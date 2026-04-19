@extends('me::printMaster2')
@section('title', 'Bajar List Print')
@section('contents')
<h3 class="text-center mb-4 text-primary">
    {{ $group->title }} - {{ formatDate($group->group_date) }}
</h3>
@php
    $req = request()->all();
    $keys = ['p_item_name', 'p_brand', 'p_source'];
    $count = count(array_filter($keys, function ($key) use ($req) {
        return array_key_exists($key, $req) && $req[$key];
    }));
@endphp
<table class="table table-bordered table-sm mt-3 table-encondex">
    <thead>
        <tr>
            <th>SL</th>
            @if(array_key_exists('p_item_name', $req) && $req['p_item_name'])
                <th>@if(app()->getLocale() === 'bn') আইটেমের নাম @else Item Name @endif</th>
            @endif
            @if(array_key_exists('p_brand', $req) && $req['p_brand'])
                <th>@if(app()->getLocale() === 'bn') ব্র্যান্ড @else Brand @endif</th>
            @endif
            @if(array_key_exists('p_source', $req) && $req['p_source'])
                <th>@if(app()->getLocale() === 'bn') উৎস @else Source @endif</th>
            @endif
            @if(array_key_exists('p_price', $req) && $req['p_price'])
                <th>@if(app()->getLocale() === 'bn') মূল্য @else Price @endif</th>
            @endif
            @if(array_key_exists('p_description', $req) && $req['p_description'])
                <th>@if(app()->getLocale() === 'bn') বিবরণ @else Description @endif</th>
            @endif
            @if(array_key_exists('p_status', $req) && $req['p_status'])
                <th>@if(app()->getLocale() === 'bn') অবস্থা @else Status @endif</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
        <tr>
            <td>{{ $loop->iteration }}</td>
            @if(array_key_exists('p_item_name', $req) && $req['p_item_name'])
                <td>
                    @if(array_key_exists('p_item_name_value', $req) && $req['p_item_name_value'])
                        {{ $item->item_name }}
                    @endif
                </td>
            @endif
            @if(array_key_exists('p_brand', $req) && $req['p_brand'])
                <td>
                    @if(array_key_exists('p_brand_value', $req) && $req['p_brand_value'])
                        {{ $item->brand }}
                    @endif
                </td>
            @endif
            @if(array_key_exists('p_source', $req) && $req['p_source'])
                <td>
                    @if(array_key_exists('p_source_value', $req) && $req['p_source_value'])
                        {{ $item->source }}
                    @endif
                </td>
            @endif
            @if(array_key_exists('p_price', $req) && $req['p_price'])
                <td class="text-right">
                    @if(array_key_exists('p_price_value', $req) && $req['p_price_value'])
                        @if($item->price > 0)
                        ৳ {{ toBanglaNumber($item->price, 2) }}
                        @endif
                    @endif
                </td>
            @endif
            @if(array_key_exists('p_description', $req) && $req['p_description'])
                <td>
                    @if(array_key_exists('p_description_value', $req) && $req['p_description_value'])
                        {{ $item->description }}
                    @endif
                </td>
            @endif
            @if(array_key_exists('p_status', $req) && $req['p_status'])
                <td>
                    @if(array_key_exists('p_status_value', $req) && $req['p_status_value'])
                         @if(app()->getLocale() === 'bn')
                            @if($item->status === 'approved')
                                অনুমোদিত
                            @elseif($item->status === 'pending')
                                অপেক্ষমান
                            @elseif($item->status === 'purchased')
                                ক্রয়কৃত
                            @elseif($item->status === 'rejected')
                                অস্বীকৃত
                            @elseif($item->status === 'hold')
                                স্থগিত
                            @else
                                {{ ucfirst($item->status) }}
                            @endif
                        @else
                            {{ ucfirst($item->status) }}
                        @endif
                    @endif
                </td>
            @endif
        </tr>
        @endforeach
        @if(array_key_exists('extra_row', $req) && is_numeric($req['extra_row']) && $req['extra_row'] > 0)
            @for($i = 0; $i < $req['extra_row']; $i++)
                <tr>
                    <td>{{ $items->count() + $i + 1 }}</td>
                    @if(array_key_exists('p_item_name', $req) && $req['p_item_name'])
                        <td>&nbsp;</td>
                    @endif
                    @if(array_key_exists('p_brand', $req) && $req['p_brand'])
                        <td>&nbsp;</td>
                    @endif
                    @if(array_key_exists('p_source', $req) && $req['p_source'])
                        <td>&nbsp;</td>
                    @endif
                    @if(array_key_exists('p_price', $req) && $req['p_price'])
                        <td>&nbsp;</td>
                    @endif
                    @if(array_key_exists('p_description', $req) && $req['p_description'])
                        <td>&nbsp;</td>
                    @endif
                    @if(array_key_exists('p_status', $req) && $req['p_status'])
                        <td>&nbsp;</td>
                    @endif
                </tr>
            @endfor
        @endif
    </tbody>
    @if(array_key_exists('p_price', $req) && $req['p_price'])
        <tfoot>
            <tr>
                <th colspan="{{ $count+1 }}" class="text-right" style="background: rgb(243, 239, 239) !important">
                    @if(app()->getLocale() === 'bn')
                        মোট মূল্য
                    @else
                        Total Amount
                    @endif
                </th>

                @if(array_key_exists('extra_row', $req) && $req['extra_row'])
                    <th class="text-right" style="background: rgb(243, 239, 239) !important">
                        @if(array_key_exists('p_price_value', $req) && $req['p_price_value'])
                            <small style="color:rgba(58, 58, 58, 0.438) !important">৳ {{ toBanglaNumber($items->sum('price') ?? 0, 2) }}</small>
                        @endif
                    </th>
                @else
                    <th class="text-right" style="background: rgb(243, 239, 239) !important">
                        @if(array_key_exists('p_price_value', $req) && $req['p_price_value'])
                           ৳ {{ toBanglaNumber($items->sum('price') ?? 0, 2) }}
                        @endif
                    </th>
                @endif

                @if(array_key_exists('p_description', $req) && $req['p_description'])
                    <th style="background: rgb(243, 239, 239) !important"></th>
                @endif
                @if(array_key_exists('p_status', $req) && $req['p_status'])
                    <th style="background: rgb(243, 239, 239) !important"></th>
                @endif
            </tr>
        </tfoot>
    @endif
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
