@extends('admin.layouts.master')
@section('title', 'Expert Table')
@section('content')
    <div class="card">
        <!-- ALERT MESSAGE -->
        <div class="p-3">
            @include('admin.layouts.partials.alerts')
        </div>
        <!-- Header -->
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">

            <h5 class="card-title mb-0">Experts</h5>

            <form method="GET" action="{{ route('admin.experts.index') }}" class="d-flex align-items-center gap-2 flex-wrap">

                <!-- Search -->
                <input name="search" type="search" class="form-control form-control-sm" placeholder="Search experts..."
                    value="{{ request('search') }}" style="width:180px;">

                <!--  Online -->
                <select name="is_online" class="form-select form-select-sm" style="width:120px;">
                    <option value="">Online</option>
                    <option value="1" {{ request('is_online') == '1' ? 'selected' : '' }}>Online</option>
                    <option value="0" {{ request('is_online') == '0' ? 'selected' : '' }}>Offline</option>
                </select>
                <!--  Approval -->
                <select name="approval_status" class="form-select form-select-sm" style="width:120px;">
                    <option value="">Approval</option>
                    <option value="pending" {{ request('approval_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('approval_status') == 'approved' ? 'selected' : '' }}>Approved</option>
                </select>


                <!-- Status -->
                 <select name="status" class="form-select form-select-sm" style="width:120px;">
                    <option value="">Status</option>
                    <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
                <!--  Search Button -->
                <button class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="Search">
                    <i class="ri-search-line"></i>
                </button>

                <!--  Reset -->
                <a href="{{ route('admin.experts.index') }}" class="btn btn-outline-secondary btn-sm"
                    data-bs-toggle="tooltip" title="Reset Filters">
                    <i class="ri-refresh-line"></i>
                </a>

                <!--  Add -->
                <a href="{{ route('admin.experts.create') }}" class="btn btn-primary btn-sm">
                    <i class="ri-add-line me-1"></i> Add
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
                        <th>Registration Code</th>
                        <th>Online</th>
                        <th>Approval Actions</th>
                        <th width="120">Status</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($experts as $expert)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <img src="{{ fileUrl($expert->profile_image) ??  asset('assets/img/default-profile-image.jpg') }}"
                                    width="35" height="35" class="rounded-circle">
                            </td>


                            <td>
                                <span class="fw-semibold">{{ $expert->name ?? ' ' }}</span>
                            </td>
                            <td>{{ $expert->phone }}</td>
                            <td class="reg-td">{{ $expert->expertDetail?->registration_code }}</td>
                            <!-- <td>{{ $expert->expertDetail?->onboarding_agent_code }}</td> -->
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input"
                                        type="checkbox"style="transform: scale(1.3); cursor: not-allowed;" disabled
                                        {{ $expert->expertDetail?->is_online ? 'checked' : '' }}>
                                </div>
                            </td>

                            {{-- <td class="status-badge">
                                @if ($expert->expertDetail?->approval_status === 'pending')
                                    <span class="badge rounded-pill bg-label-secondary">Pending</span>
                                @elseif($expert->expertDetail?->approval_status === 'approved')
                                    <span class="badge rounded-pill bg-label-primary">Approved</span>
                                @endif
                            </td> --}}
                            <td>
                                @if ($expert->expertDetail?->approval_status === 'approved')
                                    <button
                                        class="btn btn-primary btn-sm rounded-pill px-2 py-1 d-flex align-items-center gap-1"
                                        disabled>
                                        <i class="bi bi-check-circle"></i> Approved
                                    </button>
                                @else
                                    <button
                                        class="btn btn-outline-warning btn-sm rounded-pill px-2 py-1 d-flex align-items-center gap-1 approveBtn"
                                        data-id="{{ $expert->id }}">
                                        <i class="bi bi-lightning-charge"></i> Pending Approval
                                    </button>
                                @endif
                            </td>

                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input toggle-status" type="checkbox"
                                        data-id="{{ $expert->id }}" style="transform: scale(1.3); cursor:pointer;"
                                        {{ $expert->status == 1 ? 'checked' : '' }}>
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
                                            <a class="dropdown-item" href="{{ route('admin.experts.edit', $expert->id) }}">
                                                <i class="ri-pencil-line me-2"></i>
                                                Edit
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.experts.show', $expert->id) }}">
                                                <i class="ri-eye-line me-2"></i>
                                                Details
                                            </a>
                                        </li>
                                        <li>
                                            <form action="{{ route('admin.experts.destroy', $expert->id) }}"
                                                method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button class="dropdown-item text-danger"
                                                    onclick="return confirm('Are you sure you want to delete this expert?')"
                                                    type="submit">
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
                            <td colspan="6" class="text-center">No users found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination (Dynamic) -->
        <div class="row px-4 pb-3 align-items-center">
            {{ $experts->links('pagination::bootstrap-5') }}
        </div>

    @endsection
    @push('scripts')
        <script>
            $(document).ready(function() {

                $(document).on('click', '.approveBtn', function() {

                    let btn = $(this);
                    let id = btn.data('id');

                    // 🔒 Prevent multiple clicks
                    if (btn.prop('disabled')) return;

                    // 🔥 Confirm
                    if (!confirm("Are you sure you want to approve this expert?")) {
                        return;
                    }

                    // 🔄 Loader state
                    btn.prop('disabled', true).html(`
            <span class="spinner-border spinner-border-sm"></span> Approving...
        `);

                    $.ajax({
                        url: '/admin/update-approve-status',
                        type: 'POST',
                        data: {
                            id: id,
                            approval_status: 1,
                            _token: '{{ csrf_token() }}'
                        },

                        success: function(response) {

                            if (response.status) {

                                // ✅ Replace with same styled Approved button
                                btn.replaceWith(`
                        <button class="btn btn-primary btn-sm rounded-pill px-2 py-1 d-flex align-items-center gap-1" disabled>
                            <i class="bi bi-check-circle"></i> Approved
                        </button>
                    `);

                                // 🔔 Success Alert
                                alert("✅ Expert Approved Successfully!");

                            } else {
                                alert('Update failed');

                                // ❌ revert button
                                btn.prop('disabled', false).html(`
                        <i class="bi bi-lightning-charge"></i> Pending Approval
                    `);
                            }
                        },

                        error: function() {

                            alert('Something went wrong');

                            // ❌ revert button
                            btn.prop('disabled', false).html(`
                    <i class="bi bi-lightning-charge"></i> Pending Approval
                `);
                        }
                    });

                });

            });
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {

                document.querySelectorAll('.toggle-status').forEach(function(toggle) {

                    toggle.addEventListener('change', function() {

                        let id = this.dataset.id;
                        let value = this.checked ? 1 : 0;
                        let checkbox = this;

                        // 🔥 Confirm Alert
                        let confirmAction = confirm(
                            value === 1 ?
                            "Are you sure you want to activate this expert?" :
                            "Are you sure you want to deactivate this expert?"
                        );

                        if (!confirmAction) {
                            // ❌ Cancel → revert toggle
                            checkbox.checked = !checkbox.checked;
                            return;
                        }

                        fetch(`/admin/experts/${id}`, {
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
                                    checkbox.checked = !value; // rollback
                                }

                            })
                            .catch(() => {
                                alert('Something went wrong');
                                checkbox.checked = !value; // rollback
                            });

                    });

                });

            });
        </script>
    @endpush
