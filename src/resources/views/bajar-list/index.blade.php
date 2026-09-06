@extends('me::master')
@section('title', 'Bajar List')
@push('buttons')
    <button class="btn btn-sm btn-encodex-create" data-bs-toggle="modal" data-bs-target="#createGroupModal">Create Group</button>
@endpush
@section('content')

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-4">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search group title..." class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-encodex-search btn-sm me-1"><i class="fa fa-search"></i> Search</button>
                        <a href="{{ route('ut.bajar-list.groups.index') }}" class="btn btn-encodex-clear btn-sm"> <i class="fa fa-eraser"></i> Reset</a>
                    </div>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-encodex table-sm table-hover striped">
                    <thead>
                        <tr class="text-center">
                            <th>SL</th>
                            <th>Title</th>
                            <th>Date</th>
                            <th>Item</th>
                            <th>Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($groups as $i => $group)
                        <tr class="text-center" style="background-color: {{ $group->color ?? '#ffffff' }};">
                            <td>{{ $i + 1 }}</td>
                            <td class="text-start fw-bold text-primary">{{ $group->title }}</td>
                            <td>{{ formatDate($group->group_date) }}</td>
                            <td>{{ count($group->items ?? []) }}</td>
                            <td class="text-end fw-bold text-success">৳ {{ toBanglaNumber($group->items->sum('price'), 2) }}</td>
                            <td>
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('ut.bajar-list.items.index', $group) }}"
                                       class="btn btn-sm btn-encodex-show" title="View">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <button class="btn btn-sm btn-encodex-edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editGroupModal{{ $group->id }}" title="Edit">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <form action="{{ route('ut.bajar-list.groups.destroy', $group) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-encodex-delete"
                                                onclick="return confirm('নিশ্চিত তো?')" title="Delete">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <div class="modal glass fade" id="createGroupModal" tabindex="-1">
        <div class="modal-dialog glass-card">
            <form class="modal-content" method="POST" action="{{ route('ut.bajar-list.groups.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Date</label>
                        <input type="date" name="group_date" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Color</label>
                        <input type="color" name="color" class="form-control form-control-color" value="#ffffff">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Create</button>
                </div>
            </form>
        </div>
    </div>
    @foreach($groups as $group)
    <div class="modal fade" id="editGroupModal{{ $group->id }}" tabindex="-1">
        <div class="modal-dialog glass-card">
            <form class="modal-content" method="POST" action="{{ route('ut.bajar-list.groups.update', $group) }}">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" value="{{ $group->title }}" required>
                    </div>
                    <div class="mb-3">
                        <label>Date</label>
                        <input type="date" name="group_date" class="form-control" value="{{ $group->group_date }}">
                    </div>
                    <div class="mb-3">
                        <label>Color</label>
                        <input type="color" name="color" class="form-control form-control-color" value="{{ $group->color ?? '#ffffff' }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
    @endforeach
@endsection
