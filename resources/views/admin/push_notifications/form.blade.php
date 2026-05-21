@extends('admin.layouts.master')

@section('title', isset($push_notification->id) ? 'Edit Push Notification' : 'Create Push Notification')

@section('content')

    <div class="card">

        <!-- Header -->
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                {{ isset($push_notification->id) ? 'Edit Push Notification' : 'Create Push Notification' }}
            </h5>

            <a href="{{ route('admin.push_notifications.index') }}" class="btn btn-sm btn-light">

                <i class="ri-arrow-left-line me-1"></i>
                Back
            </a>
        </div>

        <!-- Body -->
        <div class="card-body">

            <form id="push_notification" method="POST"
                action="{{ isset($push_notification->id)
                    ? route('admin.push_notifications.update', $push_notification->id)
                    : route('admin.push_notifications.store') }}">

                @csrf

                @if (isset($push_notification->id))
                    @method('PUT')
                @endif

                <div class="row g-3">

                    <!-- Title -->
                    <div class="col-lg-4 col-md-6 col-12">
                        <label class="form-label">
                            Title <span class="text-danger">*</span>
                        </label>

                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="ri-notification-3-line"></i>
                            </span>

                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                value="{{ old('title', $push_notification->title ?? '') }}"
                                placeholder="Enter notification title">

                            @error('title')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <!-- Send Type -->
                    <div class="col-lg-4 col-md-6 col-12">
                        <label class="form-label">
                            Send Type <span class="text-danger">*</span>
                        </label>

                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="ri-send-plane-line"></i>
                            </span>

                            <select id="send_type" name="send_type"
                                class="form-select @error('send_type') is-invalid @enderror">

                                <option value="">Select Type</option>

                                <option value="all"
                                    {{ old('send_type', $push_notification->send_type ?? '') == 'all' ? 'selected' : '' }}>
                                    All Users
                                </option>

                                <option value="location"
                                    {{ old('send_type', $push_notification->send_type ?? '') == 'location' ? 'selected' : '' }}>
                                    Location Wise
                                </option>
                                <option value="single_user"
                                    {{ old('send_type', $push_notification->send_type ?? '') == 'single_user' ? 'selected' : '' }}>
                                    Single User
                                </option>

                            </select>

                            @error('send_type')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    <!-- Single User -->
                    {{-- <div class="col-lg-4 col-md-6 col-12" id="user_div"
                        style="{{ old('send_type', $push_notification->send_type ?? '') == 'single_user' ? '' : 'display:none;' }}">

                        <label class="form-label">
                            Select User <span class="text-danger">*</span>
                        </label>

                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="ri-user-line"></i>
                            </span>

                            <select name="user_id" id="user_id"
                                class="form-select @error('user_id') is-invalid @enderror">

                                <option value="">Select User</option>

                            </select>

                        </div>

                        @error('user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div> --}}
                    <div class="col-lg-4 col-md-6 col-12" id="user_div"
                        style="{{ old('send_type', $push_notification->send_type ?? '') == 'single_user' ? '' : 'display:none;' }}">

                        <label class="form-label fw-semibold mb-2">
                            Select User <span class="text-danger">*</span>
                        </label>

                        <div class="custom-dropdown position-relative">

                            <!-- Selected Button -->
                            <button type="button"
                                class="form-control text-start d-flex justify-content-between align-items-center"
                                id="selectedUserBtn">

                                <span id="selectedUserText">Select User</span>

                                <i class="ri-arrow-down-s-line"></i>
                            </button>

                            <!-- Dropdown Box -->
                            <div class="dropdown-box shadow-sm d-none position-absolute w-100 bg-white" id="dropdownBox"
                                style="z-index:999; max-height:300px; overflow:auto;">

                                <!-- Search -->
                                <div class="p-2 border-bottom bg-white sticky-top">
                                    <input type="text" class="form-control" id="searchUser" placeholder="Search User...">
                                </div>

                                <!-- User List -->
                                <div class="user-list" id="userList">

                                    <!-- AJAX USERS LOAD HERE -->

                                </div>

                            </div>
                        </div>

                        <!-- Hidden Input -->
                        <input type="hidden" name="user_id" id="user_id"
                            value="{{ old('user_id', $push_notification->user_id ?? '') }}">

                        @error('user_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <!-- Service Location -->
                    <div class="col-lg-4 col-md-6 col-12" id="location_div"
                        style="{{ old('send_type', $push_notification->send_type ?? '') == 'location' ? '' : 'display:none;' }}">

                        <label class="form-label">
                            Service Location <span class="text-danger">*</span>
                        </label>

                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="ri-map-pin-line"></i>
                            </span>

                            <select name="location_id" class="form-select @error('location_id') is-invalid @enderror">

                                <option value="">Select Location</option>

                                @foreach ($locations as $location)
                                    <option value="{{ $location->id }}"
                                        {{ old('location_id', $push_notification->location_id ?? '') == $location->id ? 'selected' : '' }}>
                                        {{ $location->address }}
                                    </option>
                                @endforeach

                            </select>

                            @error('location_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <!-- User Type -->
                    <div class="col-lg-4 col-md-6 col-12">
                        <label class="form-label">
                            User Type
                        </label>

                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="ri-user-line"></i>
                            </span>

                            <select name="user_type" class="form-select @error('user_type') is-invalid @enderror">

                                <option value="">Select User Type</option>

                                <option value="user"
                                    {{ old('user_type', $push_notification->user_type ?? '') == 'user' ? 'selected' : '' }}>
                                    User
                                </option>

                                <option value="expert"
                                    {{ old('user_type', $push_notification->user_type ?? '') == 'expert' ? 'selected' : '' }}>
                                    Expert
                                </option>

                            </select>

                            @error('user_type')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <!-- Schedule Type -->
                    <div class="col-lg-4 col-md-6 col-12">
                        <label class="form-label">
                            Schedule Type
                        </label>

                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="ri-alarm-line"></i>
                            </span>

                            <select name="schedule_type" id="schedule_type"
                                class="form-select @error('schedule_type') is-invalid @enderror">

                                <option value="instant"
                                    {{ old('schedule_type', $push_notification->schedule_type ?? 'instant') == 'instant' ? 'selected' : '' }}>
                                    Instant
                                </option>

                                <option value="scheduled"
                                    {{ old('schedule_type', $push_notification->schedule_type ?? '') == 'scheduled' ? 'selected' : '' }}>
                                    Scheduled
                                </option>

                            </select>

                            @error('schedule_type')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <!-- Scheduled At -->
                    <div class="col-lg-4 col-md-6 col-12" id="scheduled_at_wrapper"
                        style="{{ old('schedule_type', $push_notification->schedule_type ?? 'instant') == 'scheduled' ? '' : 'display:none;' }}">

                        <label class="form-label">
                            Scheduled At
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                <i class="ri-time-line"></i>
                            </span>

                            <input type="datetime-local" name="scheduled_at" id="scheduled_at"
                                class="form-control @error('scheduled_at') is-invalid @enderror"
                                value="{{ old('scheduled_at', isset($push_notification->scheduled_at) ? \Carbon\Carbon::parse($push_notification->scheduled_at)->format('Y-m-d\TH:i') : '') }}">

                            @error('scheduled_at')
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

                                <option value="1"
                                    {{ old('status', $push_notification->status ?? 1) == 1 ? 'selected' : '' }}>
                                    Active
                                </option>

                                <option value="0"
                                    {{ old('status', $push_notification->status ?? 1) == 0 ? 'selected' : '' }}>
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

                    <!-- Message -->
                    <div class="col-12">
                        <label class="form-label">
                            Message <span class="text-danger">*</span>
                        </label>

                        <!-- Quill Editor -->
                        <div id="editor" style="height: 200px;"></div>

                        <!-- Hidden Input -->
                        <input type="hidden" name="message" id="message"
                            value="{{ old('message', $push_notification->message) }}">

                        @error('message')
                            <div class="text-danger">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>


                </div>

                <!-- Submit -->
                <div class="mt-4">
                    <button class="btn btn-primary">
                        <i class="ri-save-line me-1"></i>

                        {{ isset($push_notification->id) ? 'Update' : 'Save' }}
                    </button>
                </div>

            </form>

        </div>

    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const sendType = document.getElementById('send_type');
            const locationDiv = document.getElementById('location_div');

            const userDiv = document.getElementById('user_div');
            const userHiddenInput = document.getElementById('user_id');

            const scheduleType = document.getElementById('schedule_type');
            const scheduledWrapper = document.getElementById('scheduled_at_wrapper');
            const scheduledAt = document.getElementById('scheduled_at');

            const dropdownBox = document.getElementById('dropdownBox');
            const selectedBtn = document.getElementById('selectedUserBtn');
            const searchInput = document.getElementById('searchUser');
            const userList = document.getElementById('userList');
            const selectedText = document.getElementById('selectedUserText');

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

                    })
                    .catch(err => console.log(err));
            }

            // =========================
            // RENDER USERS (FIXED EVENT DELEGATION)
            // =========================
            function renderUsers(users) {

                userList.innerHTML = '';

                users.forEach(user => {

                    const div = document.createElement('div');

                    div.className = "user-item px-3 py-2 border-bottom";
                    div.style.cursor = "pointer";

                    div.dataset.id = user.id;
                    div.dataset.name = user.name + (user.phone ? ` (${user.phone})` : '');

                    div.innerHTML = div.dataset.name;

                    userList.appendChild(div);
                });
            }

            // =========================
            // CLICK (EVENT DELEGATION - FIXED)
            // =========================
            userList.addEventListener('click', function(e) {

                const item = e.target.closest('.user-item');

                if (!item) return;

                userHiddenInput.value = item.dataset.id;
                selectedText.innerText = item.dataset.name;

                dropdownBox.classList.add('d-none');
            });

            // =========================
            // SEARCH (AUTO SUGGEST)
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

                // priority startsWith
                filtered.sort((a, b) => {

                    let aStart = a.name.toLowerCase().startsWith(value) ? 1 : 0;
                    let bStart = b.name.toLowerCase().startsWith(value) ? 1 : 0;

                    return bStart - aStart;

                });

                renderUsers(filtered);
            });

            // =========================
            // DROPDOWN TOGGLE
            // =========================
            selectedBtn.addEventListener('click', function() {
                dropdownBox.classList.toggle('d-none');
            });

            document.addEventListener('click', function(e) {
                if (!selectedBtn.contains(e.target) && !dropdownBox.contains(e.target)) {
                    dropdownBox.classList.add('d-none');
                }
            });

            // =========================
            // SEND TYPE
            // =========================
            function toggleSendTypeFields() {

                if (sendType.value === 'location') {
                    locationDiv.style.display = 'block';
                } else {
                    locationDiv.style.display = 'none';
                }

                if (sendType.value === 'single_user') {
                    userDiv.style.display = 'block';
                    loadUsers();
                } else {
                    userDiv.style.display = 'none';
                    userHiddenInput.value = '';
                    selectedText.innerText = 'Select User';
                }
            }

            // =========================
            // SCHEDULE
            // =========================
            function toggleScheduleField() {

                if (scheduleType.value === 'scheduled') {
                    scheduledWrapper.style.display = 'block';
                } else {
                    scheduledWrapper.style.display = 'none';
                }
            }

            // =========================
            // INIT
            // =========================
            toggleSendTypeFields();
            toggleScheduleField();

            sendType.addEventListener('change', toggleSendTypeFields);
            scheduleType.addEventListener('change', toggleScheduleField);

        });
    </script>
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Quill
            var quill = new Quill('#editor', {
                theme: 'snow',
                placeholder: 'Write message here...',
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline'],
                        [{
                            'list': 'ordered'
                        }, {
                            'list': 'bullet'
                        }],
                        ['link', 'image']
                    ]
                }
            });

            // Load existing description for edit
            var descriptionInput = document.getElementById('message');
            if (descriptionInput.value) {
                quill.root.innerHTML = descriptionInput.value;
            }

            // On form submit, copy content to hidden input
            var form = document.getElementById('push_notification');
            form.addEventListener('submit', function() {
                descriptionInput.value = quill.root.innerHTML.trim();
            });
        });
    </script>
@endpush
