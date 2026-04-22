@extends('me::master')
@section('title', $group->title)
@push('buttons')
    <button class="btn btn-sm btn-encodex-payment" data-bs-toggle="modal" data-bs-target="#printSettingsModal"><i class="fa fa-cog"></i></button>
    <a href="{{ route('ut.bajar-list.groups.index') }}" class="btn btn-sm btn-encodex-list"><i class="fa fa-list"></i></a>
    <a href  = "javascript:void(0)" id = "printBtn" target = "_blank" class = "btn btn-sm btn-encodex-print">
        <i class = "fa fa-print"></i>
    </a>
    <button class="btn btn-sm btn-encodex-create" data-bs-toggle="modal" data-bs-target="#createItemModal"><i class="fa fa-plus"></i></button>
@endpush

@section('content')

    <div class="card">
        <div class="card-body">
            <form method="GET" class="filterForm" class="mb-3">
                <div class="row mb-2">
                    <div class="col-md-3">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search items..." class="form-control form-control-sm">
                        <input type="hidden" name="ids" id="idsInput" value="{{ request('ids') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Status</option>
                            <option value="pending" @if(request('status')=='pending') selected @endif>Pending</option>
                            <option value="purchased" @if(request('status')=='purchased') selected @endif>Purchased</option>
                            <option value="hold" @if(request('status')=='hold') selected @endif>Hold</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-encodex-search btn-sm me-1"><i class="fa fa-search"></i> Filter</button>
                        <a href="{{ route('ut.bajar-list.items.index', $group) }}" class="btn btn-encodex-clear btn-sm"> <i class="fa fa-eraser"></i> Reset</a>
                    </div>
                </div>

                    <!-- Modal -->
                <div class="modal fade" id="printSettingsModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                    <div class="modal-dialog glass-card">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Print Settings <input type="checkbox" value="1" class="col-toggle form-check-input select-all-settings"></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <table class="table table-sm mb-0">
                                    <tbody>
                                        <tr>
                                            <th>Item Name</th>
                                            <td>
                                                <label class="me-3"><input type="checkbox" value="1" class="col-toggle form-check-input" name="p_item_name" @if(request('p_item_name')==1 ) checked @endif> Column </label>
                                                <label><input type="checkbox" value="1" class="col-toggle form-check-input" name="p_item_name_value" @if(request('p_item_name_value')==1) checked @endif> Value </label>
                                            </td>
                                        </tr>
                                        <tr>    
                                            <th>Brand</th>
                                            <td>
                                                <label class="me-3"><input type="checkbox" value="1" class="col-toggle form-check-input" name="p_brand" @if(request('p_brand')==1) checked @endif> Column </label>
                                                <label><input type="checkbox" value="1" class="col-toggle form-check-input" name="p_brand_value" @if(request('p_brand_value')==1) checked @endif> Value </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Source</th>
                                            <td>
                                                <label class="me-3"><input type="checkbox" value="1" class="col-toggle form-check-input" name="p_source" @if(request('p_source')==1) checked @endif> Column </label>
                                                <label><input type="checkbox" value="1" class="col-toggle form-check-input" name="p_source_value" @if(request('p_source_value')==1) checked @endif> Value </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Price</th>
                                            <td>
                                                <label class="me-3"><input type="checkbox" value="1" class="col-toggle form-check-input" name="p_price" @if(request('p_price')==1) checked @endif> Column </label>
                                                <label><input type="checkbox" value="1" class="col-toggle form-check-input" name="p_price_value" @if(request('p_price_value')==1) checked @endif> Value </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Description</th>
                                            <td>
                                                <label class="me-3"><input type="checkbox" value="1" class="col-toggle form-check-input" name="p_description" @if(request('p_description')==1) checked @endif> Column </label>
                                                <label><input type="checkbox" value="1" class="col-toggle form-check-input" name="p_description_value" @if(request('p_description_value')==1) checked @endif> Value </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td>
                                                <label class="me-3"><input type="checkbox" value="1" class="col-toggle form-check-input" name="p_status" @if(request('p_status')==1) checked @endif> Column </label>
                                                <label><input type="checkbox" value="1" class="col-toggle form-check-input" name="p_status_value" @if(request('p_status_value')==1) checked @endif> Value </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Extra</th>
                                            <td>
                                                <input type="number" name="extra_row" id="" class="form-control form-control-sm" placeholder="Add extra empty rows at the end of the table" value="{{ request('extra_row') }}">
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-sm btn-encodex-save">Save Settings</button>
                            </div>
                        </div>
                    </div>
                </div>

            </form>
            <div class="table-responsive">
                <table class="table table-encodex table-sm table-hover striped" id="mainTable">
                    <thead>
                        <tr class="text-center">
                            <th><input type="checkbox" id="checkAll" class="form-check-input" onclick="markAll(this.checked)"> SL</th>
                            <th class="col-item_name">Item Name</th>
                            <th class="col-brand">Brand</th>
                            <th class="col-source">Source</th>
                            <th class="col-price">Price</th>
                            <th class="col-description">Description</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $i => $item)
                        <tr class="text-center">
                            <td>
                                <input type="checkbox" class="row-check form-check-input" value="{{ $item->id }}" @if(request('ids') && in_array($item->id, explode(',', request('ids')))) checked @endif> {{ $i+1 }}
                            </td>
                            <td class="col-item_name" ondblclick="makeEditable(this, '{{ route('ut.bajar-list.items.update', [$group, $item]) }}', 'item_name')">{{ $item->item_name }}</td>
                            <td class="col-brand" ondblclick="makeEditable(this, '{{ route('ut.bajar-list.items.update', [$group, $item]) }}', 'brand')">{{ $item->brand }}</td>
                            <td class="col-source" ondblclick="makeEditable(this, '{{ route('ut.bajar-list.items.update', [$group, $item]) }}', 'source')">{{ $item->source }}</td>
                            <td class="col-price text-end" ondblclick="makeEditable(this, '{{ route('ut.bajar-list.items.update', [$group, $item]) }}', 'price')">{{ toBanglaNumber($item->price, 2) }}</td>
                            <td class="col-description" ondblclick="makeEditable(this, '{{ route('ut.bajar-list.items.update', [$group, $item]) }}', 'description')">{{ $item->description }}</td>
                            <td>
                                @if($item->status === 'pending')
                                    <form method="POST" action="{{ route('ut.bajar-list.items.update', [$group, $item]) }}" style="display:inline;">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="item_name" value="{{ $item->item_name }}">
                                        <input type="hidden" name="brand" value="{{ $item->brand }}">
                                        <input type="hidden" name="source" value="{{ $item->source }}">
                                        <input type="hidden" name="price" value="{{ $item->price }}">
                                        <input type="hidden" name="description" value="{{ $item->description }}">
                                        <input type="hidden" name="status" value="purchased">
                                        <button type="submit" class="btn btn-sm btn-encodex-active"><i class="fa fa-check"></i></button>
                                    </form>
                                @elseif($item->status === 'hold')
                                    <span class="badge bg-warning text-dark">Hold</span>
                                @else
                                    <span class="badge bg-success">Purchased</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex justify-content-center gap-1">
                                    <button class="btn btn-sm btn-encodex-edit" data-bs-toggle="modal" data-bs-target="#editItemModal{{ $item->id }}"><i class="fa fa-edit"></i></button>
                                    <form action="{{ route('ut.bajar-list.items.destroy', [$group, $item]) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-encodex-delete " onclick="return confirm('Delete this item?')"><i class="fa fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="text-center">
                            <th colspan="5" class="text-end">Total:</th>
                            <th class="text-end col-price">{{ toBanglaNumber($items->sum('price'), 2) }}</th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>
    </div>

    @foreach($items as $item)
            <!-- Edit Modal -->
        <div class="modal fade" id="editItemModal{{ $item->id }}" tabindex="-1">
            <div class="modal-dialog glass-card">
                <form class="modal-content" method="POST" action="{{ route('ut.bajar-list.items.update', [$group, $item]) }}">
                    @csrf @method('PUT')
                    <div class="modal-header bg-white">
                        <h5 class="modal-title">Edit Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body bg-white">
                        <div class="mb-3">
                            <label>Item Name</label>
                            <input type="text" name="item_name" class="form-control" value="{{ $item->item_name }}" required>
                        </div>
                        <div class="mb-3">
                            <label>Brand</label>
                            <input type="text" name="brand" class="form-control" value="{{ $item->brand }}">
                        </div>
                        <div class="mb-3">
                            <label>Source</label>
                            <input type="text" name="source" class="form-control" value="{{ $item->source }}">
                        </div>
                        <div class="mb-3">
                            <label>Price</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="{{ $item->price }}">
                        </div>
                        <div class="mb-3">
                            <label>Description</label>
                            <textarea name="description" class="form-control">{{ $item->description }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label>Status</label>
                            <select name="status" class="form-select">
                                <option value="pending" @if($item->status=='pending') selected @endif>Pending</option>
                                <option value="purchased" @if($item->status=='purchased') selected @endif>Purchased</option>
                                <option value="hold" @if($item->status=='hold') selected @endif>Hold</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-white">
                        <button type="submit" class="btn btn-sm btn-encodex-save">Update Item</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    <div class="modal fade" id="createItemModal" tabindex="-1">
        <div class="modal-dialog glass-card">
            <form class="modal-content" method="POST" action="{{ route('ut.bajar-list.items.store', $group) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Item Name</label>
                        <input type="text" name="item_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Brand</label>
                        <input type="text" name="brand" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Source</label>
                        <input type="text" name="source" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Price</label>
                        <input type="number" step="0.01" name="price" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Status</label>
                        <select name="status" class="form-select">
                            <option value="pending">Pending</option>
                            <option value="purchased">Purchased</option>
                            <option value="hold">Hold</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-sm btn-encodex-save">Add Item</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('js')
<script>
$(document).ready(function () {

    // Mark all / unmark all
    window.markAll = function (val) {
        $('.row-check').prop('checked', val);
        $('#checkAll').prop('checked', val);
        updateIds();
    };

    // Row checkbox change
    $(document).on('change', '.row-check', function () {
        updateIds();
    });
    $('.row-check').closest('td').on('click', function (e) {
        if ($(e.target).hasClass('row-check')) return;
        $(this).children('.row-check').trigger('click');
    });
    // Check all checkbox change
    $(document).on('change', '#checkAll', function () {
        markAll($(this).is(':checked'));
    });

    // Update IDs
    function updateIds() {

        let checkedIds = [];
        $('.row-check:checked').each(function () {
            checkedIds.push($(this).val());
        });

        let total = $('.row-check').length;
        let input = $('#idsInput');

        if (!input.length) return;

        if (checkedIds.length === total) {
            input.val('');
        } else {
            input.val(checkedIds.join(','));
        }
    }

    $('#printBtn').on('click', function () {

        // form থেকে সব input collect
        let query = $('.filterForm').serialize();

        // print url
        let url = "{{ route('ut.bajar-list.items.print', $group) }}?" + query;

        // open in new tab
        window.open(url, '_blank');
    });

    updateIds();
});

$('.select-all-settings').on('change', function () {
    let isChecked = $(this).is(':checked');

    $('.col-toggle.form-check-input[name^="p_"]').prop('checked', isChecked);
});


// ================= Inline Edit (jQuery friendly but mostly same) =================

function makeEditable(td, url, field) {
    var $td = $(td);

    if ($td.attr('contenteditable') === 'true') return;

    var oldValue = $td.text();
    $td.attr('contenteditable', true).focus();

    document.execCommand('selectAll', false, null);

    var $tr = $td.closest('tr');
    var fields = ['item_name', 'brand', 'source', 'price', 'description', 'status'];
    var fieldMap = {};

    fields.forEach(function (f) {
        var cell = null;

        $tr.children().each(function () {
            var ondbl = $(this).attr('ondblclick');
            if (ondbl && ondbl.includes("'" + f + "'")) {
                cell = this;
            }
        });

        if (cell) {
            fieldMap[f] = $(cell).text().trim();
        } else if (f === 'status') {
            var statusCell = $tr.find('td:nth-child(8)');
            var badge = statusCell.find('.badge');

            if (badge.length) {
                fieldMap['status'] = badge.text().trim().toLowerCase();
            } else if (statusCell.find('form').length) {
                fieldMap['status'] = 'pending';
            }
        }
    });

    // Get item ID
    var itemId = null;
    var editBtn = $tr.find('.btn-encodex-edit');

    if (editBtn.length) {
        var modalId = editBtn.data('bs-target');
        var match = modalId ? modalId.match(/#editItemModal(\d+)/) : null;
        if (match) itemId = match[1];
    }

    if (!itemId && url) {
        var urlMatch = url.match(/\/(\d+)(?:\?.*)?$/);
        if (urlMatch) itemId = urlMatch[1];
    }

    if (!itemId) {
        alert('Item ID not found.');
        $td.text(oldValue).attr('contenteditable', false);
        return;
    }

    var apiUrl = '/api/bajar-list/items/' + itemId;

    function saveEdit() {
        var value = $td.text().trim();
        $td.attr('contenteditable', false);

        if (value !== oldValue) {
            var data = {};

            fields.forEach(function (f) {
                if (f === field) {
                    data[f] = value;
                } else if (fieldMap[f] !== undefined) {
                    data[f] = fieldMap[f];
                }
            });

            $td.text('...');

            $.ajax({
                url: apiUrl,
                method: 'PUT',
                data: JSON.stringify(data),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function () {
                    $td.text(value);
                    $td.css('background-color', '#d4edda');
                    setTimeout(() => $td.css('background-color', ''), 800);
                },
                error: function () {
                    $td.text(oldValue);
                    $td.css('background-color', '#f8d7da');
                    setTimeout(() => $td.css('background-color', ''), 1200);
                    alert('Failed to save.');
                }
            });
        } else {
            $td.text(oldValue);
        }
    }

    function cancelEdit() {
        $td.attr('contenteditable', false).text(oldValue);
    }

    $td.on('blur', saveEdit);

    $td.on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $td.blur();
        }
        if (e.key === 'Escape') {
            e.preventDefault();
            cancelEdit();
        }
    });
}
</script>
@endpush
