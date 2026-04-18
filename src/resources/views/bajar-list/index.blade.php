@extends('me::master')
@section('title', 'Bajar List Groups')
@push('buttons')
    <button class="btn btn-sm btn-encodex-create" data-bs-toggle="modal" data-bs-target="#createGroupModal">Create Group</button>
@endpush
@section('content')

    <div class="card glass-cardX mb-3">
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
            <div class="row">
                @foreach($groups as $group)
                    <div class="col-md-3 mb-3">

                        <div class="card shadow-sm border-0 rounded-3 mb-3 hover" style="background-color: {{ $group->color ?? '#ffffff' }};">
                            <div class="card-body p-3">
                                <!-- Top Section: Title and Item Count -->
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="card-title fw-bold mb-0 text-primary">{{ $group->title }}</h6> 
                                        <sup class="badge text-primary">{{ $group->items_count ?? 0 }}</sup>
                                    </div>
                                </div>

                                <!-- Center Section: Amount -->
                                <div class="text-center my-3">
                                    <h4 class="fw-bold mb-0 text-success">
                                        ৳ {{ number_format($group->total_amount ?? 0, 2) }}
                                    </h4>
                                </div>

                                <!-- Bottom Section: Date and Actions -->
                                <div class="d-flex justify-content-between align-items-end pt-2 border-top">
                                    <!-- Left Bottom: Date -->
                                    <div class="text-muted small align-self-center">
                                        <i class="bi bi-calendar-event me-1"></i>
                                        {{ formatDate($group->group_date) }}
                                    </div>

                                    <!-- Right Bottom: Action Buttons -->
                                    <div class="d-flex gap-2">
                                        <!-- View Button -->
                                        <a href="{{ route('ut.bajar-list.items.index', $group) }}" 
                                        class="btn btn-sm btn-encodex-show" title="View">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>

                                        <!-- Edit Button -->
                                        <button class="btn btn-sm btn-encodex-edit" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editGroupModal{{ $group->id }}" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <!-- Delete Button -->
                                        <form action="{{ route('ut.bajar-list.groups.destroy', $group) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-encodex-delete" 
                                                    onclick="return confirm('নিশ্চিত তো?')" title="Delete">
                                                <i class="bi bi-trash3-fill"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>
                    <!-- Edit Modal -->
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
@endsection

@push('css')
<style>
    .hover{
        transition: all 0.3s ease; /* মসৃণ ট্রানজিশন */
    }
    .hover:hover {
        transform: translateY(-5px); /* উপরে সামান্য উঠে আসবে */
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; /* হালকা শ্যাডো বাড়বে */
        border-color: #0d6efd !important; /* বর্ডারে হালকা কালার আসবে */
    }
</style>
@endpush