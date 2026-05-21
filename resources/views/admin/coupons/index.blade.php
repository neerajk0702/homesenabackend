@extends('admin.layouts.master')

@section('title', 'Coupons')

@section('content')

    <div class="card">

        <!-- ALERT MESSAGE -->
        <div class="p-3">
            @include('admin.layouts.partials.alerts')
        </div>

        <!-- Header -->
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">

            <h5 class="card-title mb-0">
                Coupons
            </h5>

            <div class="d-flex align-items-center gap-3 flex-wrap">

                <!-- Search Form -->
                <form method="GET" action="{{ route('admin.coupons.index') }}"
                    class="d-flex align-items-center gap-2 flex-wrap">

                    <!-- Search -->
                    <div class="d-flex align-items-center">

                        <span class="me-2 small fw-semibold">
                            Search:
                        </span>

                        <input type="search" name="search" class="form-control form-control-sm"
                            placeholder="Search Coupon..." value="{{ request('search') }}" style="width:180px;">

                    </div>

                    <!-- Status -->
                    <select name="status" class="form-select form-select-sm" style="width:120px;">

                        <option value="">Status</option>

                        <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>
                            Active
                        </option>

                        <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>
                            Inactive
                        </option>

                    </select>

                    <!-- Search Button -->
                    <button class="btn btn-primary btn-sm">

                        <i class="ri-search-line"></i>

                    </button>

                    <!-- Reset -->
                    <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary btn-sm">

                        <i class="ri-refresh-line"></i>

                    </a>

                </form>

                <!-- Add Button -->
                <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary btn-sm">

                    <i class="ri-add-line me-1"></i>
                    Add

                </a>

            </div>

        </div>

        <hr class="my-0 mb-2">

        <!-- Table -->
        <div class="table-responsive px-4 pb-3">

            <table class="table table-hover align-middle table-bordered">

                <thead class="bg-label-secondary">

                    <tr>

                        <th width="60">#</th>

                        <th>Coupon Code</th>

                        <th>Discount</th>

                        <th>Coupon For</th>

                        <th>Per User Limit</th>

                        <th>Start Date</th>

                        <th>End Date</th>

                        <th width="120">Status</th>

                        <th width="120" class="text-center">
                            Action
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($coupons as $key => $coupon)
                        <tr>

                            <!-- Pagination Index -->
                            <td>
                                {{ $coupons->firstItem() + $key }}
                            </td>

                            <!-- Coupon Code -->
                            <td>

                                <span class="badge bg-label-primary">

                                    {{ $coupon->code }}

                                </span>

                            </td>

                            <!-- Discount -->
                            <td>

                                @if ($coupon->discount_type == 'fixed')
                                    ₹{{ $coupon->discount_value }}
                                @else
                                    {{ $coupon->discount_value }}%
                                @endif

                            </td>

                            <!-- Coupon For -->
                            <td>

                                {{ ucfirst($coupon->coupon_for) }}

                            </td>

                            <!-- Per User -->
                            <td>

                                {{ $coupon->per_user_limit ?? '-' }}

                            </td>

                            <!-- Start Date -->
                            <td>

                                {{ \Carbon\Carbon::parse($coupon->start_date)->format('d M Y') }}

                            </td>

                            <!-- End Date -->
                            <td>

                                {{ \Carbon\Carbon::parse($coupon->end_date)->format('d M Y') }}

                            </td>

                            <!-- Status -->
                            <td>

                                <div class="form-check form-switch">

                                    <input type="checkbox" class="form-check-input toggle-status"
                                        data-id="{{ $coupon->id }}" style="transform: scale(1.3); cursor:pointer;"
                                        {{ $coupon->status ? 'checked' : '' }}>

                                </div>

                            </td>

                            <!-- Action -->
                            <td class="text-center">

                                <div class="dropdown">

                                    <button type="button"
                                        class="btn btn-sm btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown">

                                        <i class="ri-more-2-line"></i>

                                    </button>

                                    <div class="dropdown-menu dropdown-menu-end">

                                        <!-- View -->
                                        <a class="dropdown-item" href="{{ route('admin.coupons.show', $coupon->id) }}">

                                            <i class="ri-eye-line me-2"></i>
                                            View

                                        </a>

                                        <!-- Edit -->
                                        <a class="dropdown-item" href="{{ route('admin.coupons.edit', $coupon->id) }}">

                                            <i class="ri-edit-box-line me-2"></i>
                                            Edit

                                        </a>

                                        <!-- Delete -->
                                        <form action="{{ route('admin.coupons.destroy', $coupon->id) }}" method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this coupon?')">

                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="dropdown-item text-danger">

                                                <i class="ri-delete-bin-line me-2"></i>
                                                Delete

                                            </button>

                                        </form>

                                    </div>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="9" class="text-center">

                                No Coupons Found

                            </td>

                        </tr>
                    @endforelse

                </tbody>

            </table>

        </div>

        <!-- Pagination -->
        <div class="row px-4 pb-3 align-items-center">

            {{ $coupons->links('pagination::bootstrap-5') }}

        </div>

    </div>

    <!-- STATUS TOGGLE -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            document.querySelectorAll('.toggle-status').forEach(function(toggle) {

                toggle.addEventListener('change', function() {

                    let id = this.dataset.id;

                    let value = this.checked ? 1 : 0;

                    let confirmAction = confirm(

                        value === 1 ?
                        "Are you sure you want to activate this coupon?" :
                        "Are you sure you want to inactivate this coupon?"

                    );

                    if (!confirmAction) {

                        this.checked = !this.checked;

                        return;
                    }

                    fetch(`/admin/coupons/${id}`, {

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
