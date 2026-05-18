@extends('admin.layouts.master')
@section('title', 'User Table')

@section('content')
    <div class="card">
        <!-- ALERT MESSAGE -->
        <div class="p-3">
            @include('admin.layouts.partials.alerts')
        </div>
        <!-- Header -->
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">

            <h5 class="card-title mb-0">Users</h5>

            <form method="GET" action="{{ route('admin.users.index') }}" class="d-flex align-items-center gap-2 flex-wrap">

                <!--  Search -->
                <input name="search" type="search" class="form-control form-control-sm" placeholder="Search..."
                    value="{{ request('search') }}" style="width:180px;">

                <!--  Status -->
                <select name="status" class="form-select form-select-sm" style="width:120px;">
                    <option value="">Status</option>
                    <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactive</option>
                </select>

                <!--  Profile -->
                <select name="profile_completed" class="form-select form-select-sm" style="width:140px;">
                    <option value="">Profile</option>
                    <option value="1" {{ request('profile_completed') == '1' ? 'selected' : '' }}>Completed</option>
                    <option value="0" {{ request('profile_completed') == '0' ? 'selected' : '' }}>Incomplete</option>
                </select>

                <!--  Buttons -->
                <button class="btn btn-primary btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="Search">
                    <i class="ri-search-line"></i>
                </button>

                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip"
                    data-bs-placement="top" title="Reset Filters">

                    <i class="ri-refresh-line"></i>
                </a>

            </form>

        </div>
        <hr class="my-0 mb-2">
        <!-- Table -->
        <div class="table-responsive px-4 pb-3">
            <table class="table table-hover align-middle table-bordered">
                <thead class="bg-label-secondary">
                    <tr>
                        <th width="60">ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Completed Profile</th>
                        <th width="120">Status</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $users->firstItem() + $loop->index }}</td>

                            <td>
                                <img src="{{ fileUrl($user->profile_image) ?? asset('assets/img/default-profile-image.jpg') }}"
                                    width="35" height="35" class="rounded-circle">
                            </td>

                            <td><span class="fw-semibold">{{ $user->name }}</span></td>
                            <td>{{ $user->phone }}</td>
                            <td>{{ $user->email }}</td>

                            <td>
                                @if ($user->profile_completed == 1)
                                    <span class="badge bg-label-primary rounded-pill">Yes</span>
                                @else
                                    <span class="badge bg-label-secondary rounded-pill">No</span>
                                @endif
                            </td>

                            <!-- ✅ STATUS TOGGLE -->
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input toggle-status" type="checkbox"
                                        data-id="{{ $user->id }}" style="transform: scale(1.3); cursor:pointer;"
                                        {{ $user->status == 1 ? 'checked' : '' }}>
                                </div>
                            </td>

                            <!-- ACTION -->
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill"
                                        data-bs-toggle="dropdown">
                                        <i class="ri-more-2-line"></i>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.users.show', $user->id) }}">
                                                <i class="ri-eye-line me-2"></i> Details
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">No users found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination (Dynamic) -->
        <div class="row px-4 pb-3 align-items-center">


            {{ $users->links('pagination::bootstrap-5') }}


        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {

                document.querySelectorAll('.toggle-status').forEach(function(toggle) {

                    toggle.addEventListener('change', function() {

                        let id = this.dataset.id;
                        let value = this.checked ? 1 : 0;
                        let checkbox = this;

                        let confirmAction = confirm(
                            value === 1 ?
                            "Are you sure you want to activate this user?" :
                            "Are you sure you want to deactivate this user?"
                        );

                        if (!confirmAction) {
                            checkbox.checked = !checkbox.checked;
                            return;
                        }

                        fetch(`/admin/users/${id}`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    _method: 'PUT',
                                    status: value
                                })
                            })
                            .then(response => response.json())
                            .then(data => {

                                if (!data.status) {
                                    alert('Update failed');
                                    checkbox.checked = !value;
                                }

                            })
                            .catch(() => {
                                alert('Something went wrong');
                                checkbox.checked = !value;
                            });

                    });

                });

            });
        </script>

    @endsection
