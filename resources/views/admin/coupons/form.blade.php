@extends('admin.layouts.master')

@section('title', isset($coupon->id) ? 'Edit Coupon' : 'Add Coupon')

@section('content')

    <div class="card">

        <!-- Header -->
        <div class="card-header d-flex justify-content-between align-items-center">

            <h5 class="mb-0">
                {{ isset($coupon->id) ? 'Edit Coupon' : 'Add Coupon' }}
            </h5>

            <a href="{{ route('admin.coupons.index') }}" class="btn btn-secondary btn-sm">

                <i class="ri-arrow-left-line me-1"></i>
                Back
            </a>

        </div>

        <!-- Body -->
        <div class="card-body">

            <form id="couponForm" method="POST"
                action="{{ isset($coupon->id) ? route('admin.coupons.update', $coupon->id) : route('admin.coupons.store') }}">

                @csrf

                @if (isset($coupon->id))
                    @method('PUT')
                @endif

                <div class="row g-3">

                    <!-- Coupon Code -->
                    <div class="col-lg-4 col-md-6 col-12">

                        <label class="form-label">
                            Coupon Code <span class="text-danger">*</span>
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                <i class="ri-coupon-3-line"></i>
                            </span>

                            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                                value="{{ old('code', $coupon->code ?? '') }}" placeholder="Enter Coupon Code">

                            @error('code')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>

                    <!-- Discount Type -->
                    <div class="col-lg-4 col-md-6 col-12">

                        <label class="form-label">
                            Discount Type <span class="text-danger">*</span>
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                <i class="ri-percent-line"></i>
                            </span>

                            <select name="discount_type" id="discount_type"
                                class="form-select @error('discount_type') is-invalid @enderror">

                                <option value="">Select Type</option>

                                <option value="fixed"
                                    {{ old('discount_type', $coupon->discount_type ?? '') == 'fixed' ? 'selected' : '' }}>
                                    Fixed
                                </option>

                                <option value="percentage"
                                    {{ old('discount_type', $coupon->discount_type ?? '') == 'percentage' ? 'selected' : '' }}>
                                    Percentage
                                </option>

                            </select>

                            @error('discount_type')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>

                    <!-- Maximum Discount Amount -->
                    <div class="col-lg-4 col-md-6 col-12" id="max_discount_div"
                        style="{{ old('discount_type', $coupon->discount_type ?? '') == 'percentage' ? '' : 'display:none;' }}">

                        <label class="form-label">
                            Maximum Discount Amount
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                <i class="ri-hand-coin-line"></i> </span>

                            <input type="number" step="0.01" name="maximum_discount_amount"
                                class="form-control @error('maximum_discount_amount') is-invalid @enderror"
                                value="{{ old('maximum_discount_amount', $coupon->maximum_discount_amount ?? '') }}"
                                placeholder="Enter Maximum Discount Amount">

                            @error('maximum_discount_amount')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>

                    <!-- Discount Value -->
                    <div class="col-lg-4 col-md-6 col-12">

                        <label class="form-label">
                            Discount Value <span class="text-danger">*</span>
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                <i class="ri-coin-line"></i> </span>

                            <input type="number" step="0.01" name="discount_value"
                                class="form-control @error('discount_value') is-invalid @enderror"
                                value="{{ old('discount_value', $coupon->discount_value ?? '') }}"
                                placeholder="Enter Discount Value">

                            @error('discount_value')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>

                    <!-- Coupon For -->
                    <div class="col-lg-4 col-md-6 col-12">

                        <label class="form-label">
                            Coupon For <span class="text-danger">*</span>
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                <i class="ri-user-settings-line"></i>
                            </span>

                            <select name="coupon_for" id="coupon_for"
                                class="form-select @error('coupon_for') is-invalid @enderror">

                                <option value="">Select</option>

                                <option value="all"
                                    {{ old('coupon_for', $coupon->coupon_for ?? '') == 'all' ? 'selected' : '' }}>
                                    All Users
                                </option>

                                <option value="new_user"
                                    {{ old('coupon_for', $coupon->coupon_for ?? '') == 'new_user' ? 'selected' : '' }}>
                                    New Users
                                </option>

                                <option value="specific_user"
                                    {{ old('coupon_for', $coupon->coupon_for ?? '') == 'specific_user' ? 'selected' : '' }}>
                                    Specific Users
                                </option>

                            </select>

                            @error('coupon_for')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>

                    <!-- Specific User -->
                    <div class="col-lg-4 col-md-6 col-12" id="user_div"
                        style="{{ old('coupon_for', $coupon->coupon_for ?? '') == 'specific_user' ? '' : 'display:none;' }}">

                        <label class="form-label fw-semibold mb-2">
                            Select User <span class="text-danger">*</span>
                        </label>

                        <div class="custom-dropdown position-relative">

                            <!-- Selected Button -->
                            <button type="button"
                                class="form-control text-start d-flex justify-content-between align-items-center"
                                id="selectedUserBtn">

                                <span id="selectedUserText">
                                    Select User
                                </span>

                                <i class="ri-arrow-down-s-line"></i>

                            </button>

                            <!-- Dropdown -->
                            <div class="dropdown-box shadow-sm d-none position-absolute w-100 bg-white" id="dropdownBox"
                                style="z-index:999; max-height:300px; overflow:auto;">

                                <!-- Search -->
                                <div class="p-2 border-bottom bg-white sticky-top">

                                    <input type="text" class="form-control" id="searchUser" placeholder="Search User...">

                                </div>

                                <!-- User List -->
                                <div class="user-list" id="userList">

                                </div>

                            </div>

                        </div>

                        <!-- Hidden Input -->
                        <input type="hidden" name="user_id" id="user_id"
                            value="{{ old('user_id', $coupon->user_id ?? '') }}">

                        @error('user_id')
                            <div class="text-danger">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                    <!-- Per User Limit -->
                    <div class="col-lg-4 col-md-6 col-12">

                        <label class="form-label">
                            Per User Limit
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                <i class="ri-repeat-line"></i>
                            </span>

                            <input type="number" name="per_user_limit"
                                class="form-control @error('per_user_limit') is-invalid @enderror"
                                value="{{ old('per_user_limit', $coupon->per_user_limit ?? '') }}"
                                placeholder="Enter Limit">

                            @error('per_user_limit')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>

                    <!-- Start Date -->
                    <div class="col-lg-4 col-md-6 col-12">

                        <label class="form-label">
                            Start Date & Time <span class="text-danger">*</span>
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                <i class="ri-calendar-event-line"></i> 
                            </span>

                            <input type="datetime-local" name="start_date" min="{{ now()->format('Y-m-d\TH:i') }}"
                                class="form-control @error('start_date') is-invalid @enderror"
                                value="{{ old('start_date', isset($coupon->start_date) ? \Carbon\Carbon::parse($coupon->start_date)->format('Y-m-d\TH:i') : '') }}">

                            @error('start_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>

                    <!-- End Date -->
                    <div class="col-lg-4 col-md-6 col-12">

                        <label class="form-label">
                            End Date & Time <span class="text-danger">*</span>
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                <i class="ri-time-line"></i>
                            </span>

                            <input type="datetime-local" name="end_date" min="{{ now()->format('Y-m-d\TH:i') }}"
                                class="form-control @error('end_date') is-invalid @enderror"
                                value="{{ old('end_date', isset($coupon->end_date) ? \Carbon\Carbon::parse($coupon->end_date)->format('Y-m-d\TH:i') : '') }}">

                            @error('end_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>

                    <!-- Status -->
                    <div class="col-lg-4 col-md-6 col-12">

                        <label class="form-label">
                            Status
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                <i class="ri-shield-check-line"></i>
                            </span>

                            <select name="status" class="form-select @error('status') is-invalid @enderror">

                                <option value="1" {{ old('status', $coupon->status ?? 1) == 1 ? 'selected' : '' }}>
                                    Active
                                </option>

                                <option value="0" {{ old('status', $coupon->status ?? 1) == 0 ? 'selected' : '' }}>
                                    Inactive
                                </option>

                            </select>

                            @error('status')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>
                    <!-- Description -->
                    <div class="col-12">

                        <label class="form-label">
                            Description
                        </label>

                        <div class="input-group">

                            <span class="input-group-text align-items-start pt-3">
                                <i class="ri-file-text-line"></i>
                            </span>

                            <textarea name="description" rows="4" class="form-control @error('description') is-invalid @enderror"
                                placeholder="Enter description...">{{ old('description', $coupon->description ?? '') }}</textarea>

                            @error('description')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>

                </div>

                <!-- Submit -->
                <div class="mt-4">

                    <button type="submit" class="btn btn-primary">

                        <i class="ri-save-line me-1"></i>

                        {{ isset($coupon->id) ? 'Update' : 'Save' }}

                    </button>

                </div>

            </form>

        </div>

    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // =========================
            // ELEMENTS
            // =========================
            const couponFor = document.getElementById('coupon_for');

            const userDiv = document.getElementById('user_div');

            const userHiddenInput = document.getElementById('user_id');

            const dropdownBox = document.getElementById('dropdownBox');

            const selectedBtn = document.getElementById('selectedUserBtn');

            const searchInput = document.getElementById('searchUser');

            const userList = document.getElementById('userList');

            const selectedText = document.getElementById('selectedUserText');

            const discountType = document.getElementById('discount_type');

            const maxDiscountDiv = document.getElementById('max_discount_div');

            let allUsers = [];

            // =========================
            // LOAD USERS
            // =========================
            function loadUsers() {

                fetch("{{ url('/get-users') }}")
                    .then(res => res.json())
                    .then(data => {

                        allUsers = data;

                        renderUsers(allUsers);

                        // EDIT CASE
                        let selectedUserId = userHiddenInput.value;

                        if (selectedUserId) {

                            let selectedUser = allUsers.find(
                                user => user.id == selectedUserId
                            );

                            if (selectedUser) {

                                selectedText.innerText =
                                    selectedUser.name +
                                    (selectedUser.phone ? ` (${selectedUser.phone})` : '');

                            }
                        }

                    })
                    .catch(err => console.log(err));
            }

            // =========================
            // RENDER USERS
            // =========================
            function renderUsers(users) {

                userList.innerHTML = '';

                if (users.length === 0) {

                    userList.innerHTML = `
                    <div class="px-3 py-2 text-muted">
                        No User Found
                    </div>
                `;

                    return;
                }

                users.forEach(user => {

                    const div = document.createElement('div');

                    div.className = "user-item px-3 py-2 border-bottom";

                    div.style.cursor = "pointer";

                    div.dataset.id = user.id;

                    div.dataset.name =
                        user.name + (user.phone ? ` (${user.phone})` : '');

                    div.innerHTML = `
                    <div class="fw-semibold">${user.name}</div>
                    ${user.phone ? `<small class="text-muted">${user.phone}</small>` : ''}
                `;

                    userList.appendChild(div);

                });
            }

            // =========================
            // SELECT USER
            // =========================
            userList.addEventListener('click', function(e) {

                const item = e.target.closest('.user-item');

                if (!item) return;

                userHiddenInput.value = item.dataset.id;

                selectedText.innerText = item.dataset.name;

                dropdownBox.classList.add('d-none');

            });

            // =========================
            // SEARCH USER
            // =========================
            searchInput.addEventListener('input', function() {

                let value = this.value.trim().toLowerCase();

                if (value === '') {

                    renderUsers(allUsers);

                    return;
                }

                let filtered = allUsers.filter(user =>

                    user.name.toLowerCase().includes(value) ||

                    (user.phone && user.phone.includes(value))
                );

                // PRIORITY SEARCH
                filtered.sort((a, b) => {

                    let aStart = a.name.toLowerCase().startsWith(value) ? 1 : 0;

                    let bStart = b.name.toLowerCase().startsWith(value) ? 1 : 0;

                    return bStart - aStart;

                });

                renderUsers(filtered);

            });

            // =========================
            // TOGGLE DROPDOWN
            // =========================
            selectedBtn.addEventListener('click', function(e) {

                e.stopPropagation();

                dropdownBox.classList.toggle('d-none');

                searchInput.focus();

            });

            document.addEventListener('click', function(e) {

                if (
                    !selectedBtn.contains(e.target) &&
                    !dropdownBox.contains(e.target)
                ) {

                    dropdownBox.classList.add('d-none');

                }

            });

            // =========================
            // TOGGLE USER FIELD
            // =========================
            function toggleUserField() {

                if (couponFor.value === 'specific_user') {

                    userDiv.style.display = 'block';

                    loadUsers();

                } else {

                    userDiv.style.display = 'none';

                    userHiddenInput.value = '';

                    selectedText.innerText = 'Select User';

                }
            }

            // =========================
            // TOGGLE MAX DISCOUNT
            // =========================
            function toggleMaxDiscount() {

                if (discountType.value === 'percentage') {

                    maxDiscountDiv.style.display = 'block';

                } else {

                    maxDiscountDiv.style.display = 'none';

                    const input = maxDiscountDiv.querySelector('input');

                    if (input) {
                        input.value = '';
                    }
                }
            }

            // =========================
            // INIT
            // =========================
            toggleUserField();

            toggleMaxDiscount();

            // =========================
            // EVENTS
            // =========================
            couponFor.addEventListener('change', toggleUserField);

            discountType.addEventListener('change', toggleMaxDiscount);

        });
    </script>
@endpush
