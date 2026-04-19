@extends('me::master')
@section('title', $group->title)
@push('buttons')
    <a href="{{ route('ut.bajar-list.groups.index') }}" class="btn btn-sm btn-encodex-list">Back</a>
    <a href="{{ route('ut.bajar-list.items.print', $group) }}?search={{ request('search') }}&status={{ request('status') }}" target="_blank" class="btn btn-sm btn-encodex-print"><i class="fa fa-print"></i> Print</a>
    <button class="btn btn-sm btn-encodex-create" data-bs-toggle="modal" data-bs-target="#createItemModal">Add Item</button>
@endpush

@section('content')

<div class="card">
    <div class="card-body">
        <form method="GET" class="mb-3">
            <div class="row">
                <div class="col-md-3">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search items..." class="form-control form-control-sm">
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
        </form>
        <div class="table-responsive">
            <table class="table table-encodex table-sm table-hover striped">
                <thead>
                    <tr class="text-center">
                        <th>Item Name</th>
                        <th>Brand</th>
                        <th>Source</th>
                        <th>Price</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr class="text-center">
                        <td ondblclick="makeEditable(this, '{{ route('ut.bajar-list.items.update', [$group, $item]) }}', 'item_name')">{{ $item->item_name }}</td>
                        <td ondblclick="makeEditable(this, '{{ route('ut.bajar-list.items.update', [$group, $item]) }}', 'brand')">{{ $item->brand }}</td>
                        <td ondblclick="makeEditable(this, '{{ route('ut.bajar-list.items.update', [$group, $item]) }}', 'source')">{{ $item->source }}</td>
                        <td ondblclick="makeEditable(this, '{{ route('ut.bajar-list.items.update', [$group, $item]) }}', 'price')" class="text-end">{{ toBanglaNumber($item->price, 2) }}</td>
                        <td ondblclick="makeEditable(this, '{{ route('ut.bajar-list.items.update', [$group, $item]) }}', 'description')">{{ $item->description }}</td>
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
                        <th colspan="3" class="text-end">Total:</th>
                        <th class="text-end">{{ toBanglaNumber($items->sum('price'), 2) }}</th>
                        <th colspan="4"></th>
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
        function makeEditable(td, url, field) {
            if (td.isContentEditable) return;
            var oldValue = td.innerText;
            td.contentEditable = true;
            td.focus();
            document.execCommand('selectAll', false, null);

            // Get the row and all field values
            var tr = td.parentElement;
            var fields = ['item_name', 'brand', 'source', 'price', 'description', 'status'];
            var fieldMap = {};
            fields.forEach(function(f) {
                var cell = null;
                // Find the cell for this field
                for (var i = 0; i < tr.children.length; i++) {
                    var c = tr.children[i];
                    if (c.getAttribute('ondblclick') && c.getAttribute('ondblclick').includes("'" + f + "'")) {
                        cell = c;
                        break;
                    }
                }
                if (cell) {
                    fieldMap[f] = cell.innerText.trim();
                } else if (f === 'status') {
                    // For status, try to get from select or badge
                    var statusCell = tr.querySelector('td:nth-child(6)');
                    if (statusCell) {
                        var badge = statusCell.querySelector('.badge');
                        if (badge) {
                            fieldMap['status'] = badge.innerText.trim().toLowerCase();
                        } else {
                            var form = statusCell.querySelector('form');
                            if (form) {
                                fieldMap['status'] = 'pending';
                            }
                        }
                    }
                }
            });

            // Extract item ID from the edit modal button's data-bs-target (or from url)
            var itemId = null;
            var editBtn = tr.querySelector('.btn-encodex-edit');
            if (editBtn && editBtn.getAttribute('data-bs-target')) {
                var modalId = editBtn.getAttribute('data-bs-target');
                var match = modalId.match(/#editItemModal(\d+)/);
                if (match) itemId = match[1];
            }
            // Fallback: try to extract from url
            if (!itemId && url) {
                var urlMatch = url.match(/\/(\d+)(?:\?.*)?$/);
                if (urlMatch) itemId = urlMatch[1];
            }
            if (!itemId) {
                alert('Item ID not found.');
                td.innerText = oldValue;
                td.contentEditable = false;
                return;
            }

            // API endpoint (adjust if needed)
            var apiUrl = '/api/bajar-list/items/' + itemId;

            function saveEdit() {
                var value = td.innerText.trim();
                td.contentEditable = false;
                if (value !== oldValue) {
                    // Prepare data for API
                    var data = {};
                    fields.forEach(function(f) {
                        if (f === field) {
                            data[f] = value;
                        } else if (fieldMap[f] !== undefined) {
                            data[f] = fieldMap[f];
                        }
                    });

                    td.innerText = '...'; // show loading

                    fetch(apiUrl, {
                        method: 'PUT',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify(data)
                    })
                    .then(function(response) {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json ? response.json() : response.text();
                    })
                    .then(function(json) {
                        td.innerText = value;
                        td.style.backgroundColor = '#d4edda'; // green highlight
                        setTimeout(function() { td.style.backgroundColor = ''; }, 800);
                    })
                    .catch(function(error) {
                        td.innerText = oldValue;
                        td.style.backgroundColor = '#f8d7da'; // red highlight
                        setTimeout(function() { td.style.backgroundColor = ''; }, 1200);
                        alert('Failed to save.');
                    });
                } else {
                    td.innerText = oldValue;
                }
            }
            function cancelEdit() {
                td.contentEditable = false;
                td.innerText = oldValue;
            }
            td.onblur = function() {
                saveEdit();
            };
            td.onkeydown = function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    td.blur();
                }
                if (e.key === 'Escape') {
                    e.preventDefault();
                    cancelEdit();
                }
            };
        }
    </script>
@endpush
