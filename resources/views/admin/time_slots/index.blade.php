@extends('admin.layouts.master')

@section('title', 'Time Slots Table')

@section('content')
    <div class="card">
        <!-- ALERT -->
        <div class="p-3">
            @include('admin.layouts.partials.alerts')
        </div>
        <!-- Header -->
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Time Slot</h5>
            <div class="d-flex align-items-center gap-3">
                <!-- Search -->
                <form method="GET" action="{{ route('admin.time_slots.index') }}" class="d-flex align-items-center gap-2">

                    <span>Search:</span>
                    <input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-sm"
                        placeholder="Search time..." style="width:180px;">

                    <!-- 🔥 STATUS FILTER -->
                    <select name="status" class="form-select form-select-sm" style="width:130px;">
                        <option value="">Status</option>
                        <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>

                    <!-- Search Button -->
                    <button class="btn btn-primary btn-sm">
                        <i class="ri-search-line"></i>
                    </button>

                    <!-- Reset -->
                    <a href="{{ route('admin.time_slots.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="ri-refresh-line"></i>
                    </a>

                </form>
                <!-- Add Button -->
                <a href="{{ route('admin.time_slots.create') }}" class="btn btn-primary btn-sm">
                    <i class="ri-add-line me-1"></i> Add
                </a>
            </div>
        </div>
        <hr class="my-0 mb-2">    
        <!-- Table -->
        <div class="table-responsive px-4 pb-3">
            <table class="table table-hover align-middle table-bordered">
                <thead class="bg-label-secondary">
                    <tr>
                        <th width="60">ID</th>
                        <th>Time (AM/PM)</th>
                        <th width="120">Status</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($slots as $slot)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <span class="fw-semibold">
                                    {{ \Carbon\Carbon::parse($slot->start_time)->format('h:i A') }}
                                </span>
                            </td>
                            <!-- STATUS TOGGLE -->

                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input toggle-status" type="checkbox"
                                        data-id="{{ $slot->id }}" style="transform: scale(1.3); cursor:pointer;"
                                        {{ $slot->status == 1 ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill"
                                        data-bs-toggle="dropdown">
                                        <i class="ri-more-2-line"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item"
                                                href="{{ route('admin.time_slots.edit', $slot->id) }}">
                                                <i class="ri-pencil-line me-2"></i> Edit
                                            </a>
                                        </li>
                                        <li>
                                            <form method="POST"
                                                action="{{ route('admin.time_slots.destroy', $slot->id) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger"
                                                    onclick="return confirm('Delete this slot?')">
                                                    <i class="ri-delete-bin-6-line me-2"></i> Delete
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">No time slots found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="row px-4 pb-3 align-items-center">
            {{ $slots->links('pagination::bootstrap-5') }}
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {

                document.querySelectorAll('.toggle-status').forEach(function(toggle) {

                    toggle.addEventListener('change', function() {

                        let id = this.dataset.id;
                        let value = this.checked ? 1 : 0;

                        //  Confirm Alert (Before Action)
                        let confirmAction = confirm(
                            value === 1 ?
                            "Are you sure you want to activate this?" :
                            "Are you sure you want to inactivate this?"
                        );

                        if (!confirmAction) {
                            this.checked = !this.checked;
                            return;
                        }

                        fetch(`/admin/time_slots/${id}`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    _method: 'PUT',
                                    status: value
                                })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (!data.status) {
                                    alert('Update failed');
                                    this.checked = !value;
                                }
                            })
                            .catch(() => {
                                alert('Something went wrong');
                                this.checked = !value;
                            });

                    });

                });

            });
        </script>
    @endsection
