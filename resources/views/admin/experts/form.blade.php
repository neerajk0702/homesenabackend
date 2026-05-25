@extends('admin.layouts.master')
@section('title', $expert->id ? 'Edit Expert' : 'Create Expert')
@section('content')
    <div class="card">
        <!-- ALERT MESSAGE -->
        <div class="p-3">
            @include('admin.layouts.partials.alerts')
        </div>
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                {{ $expert->id ? 'Edit Expert' : 'Create Expert' }}
            </h5>
            <a href="{{ route('admin.experts.index') }}" class="btn btn-sm btn-light">
                <i class="ri-arrow-left-line me-1"></i> Back
            </a>
        </div>
        <div class="card-body">
            <form method="POST"
                action="{{ $expert->id ? route('admin.experts.update', $expert->id) : route('admin.experts.store') }}"
                enctype="multipart/form-data">
                @csrf
                @if ($expert->id)
                    @method('PUT')
                @endif
                <div class="row">
                    <!-- Profile Image -->
                    <div class="col-lg-4 col-md-6 col-12 mb-3">
                        <label class="form-label">Profile Image</label>

                        <input type="file" name="profile_image" id="imageInput"
                            class="form-control @error('profile_image') is-invalid @enderror">

                        @error('profile_image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        <!-- Preview -->
                        <div class="mt-2">
                            <img id="previewImage"
                                src="{{ $expert->profile_image ? asset($expert->profile_image) : asset('assets/img/default-profile-image.jpg') }}"
                                width="70" height="70" class="rounded-circle border">
                        </div>
                    </div>
                    <!-- Name -->
                    <div class="col-lg-4 col-md-6 col-12 mb-3">
                        <label class="form-label">Name</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="ri-user-line"></i>
                            </span>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                placeholder="Enter name" value="{{ old('name', $expert->name) }}">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <!-- ---------------- -->
                    <!-- Phone -->
                    <div class="col-lg-4 col-md-6 col-12 mb-3">
                        <label class="form-label">Phone</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="ri-phone-line"></i>
                            </span>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                placeholder="Enter phone" value="{{ old('phone', $expert->phone) }}">
                            @error('phone')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    <!-- Email -->
                    <div class="col-lg-4 col-md-6 col-12 mb-3">
                        <label class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="ri-mail-line"></i>
                            </span>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                placeholder="Enter email" value="{{ old('email', $expert->email) }}">
                            @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    <!-- Gender -->
                    <div class="col-lg-4 col-md-6 col-12 mb-3">
                        <label class="form-label">Gender</label>

                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="ri-user-star-line"></i>
                            </span>

                            <select name="gender" class="form-select @error('gender') is-invalid @enderror">

                                <option value="">Select Gender</option>

                                <option value="male" {{ old('gender', $expert->gender) == 'male' ? 'selected' : '' }}>
                                    Male
                                </option>

                                <option value="female" {{ old('gender', $expert->gender) == 'female' ? 'selected' : '' }}>
                                    Female
                                </option>

                                <option value="other" {{ old('gender', $expert->gender) == 'other' ? 'selected' : '' }}>
                                    Other
                                </option>

                            </select>

                            @error('gender')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    <!-- password -->
                    {{-- <div class="col-lg-4 col-md-6 col-12 mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="ri-mail-line"></i>
                            </span>
                            <input type="password" name="password"
                                class="form-control @error('password') is-invalid @enderror" placeholder="Enter password">
                            @error('password')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        @if ($expert->exists)
                            <small class="text-muted">Leave blank to keep old password</small>
                        @endif
                    </div> --}}

                    @if (!$expert->exists)
                        <!-- Device ID -->
                        <div class="col-lg-4 col-md-6 col-12 mb-3">
                            <label class="form-label">Device ID</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="ri-fingerprint-line"></i>
                                </span>
                                <input type="text" class="form-control @error('device_id') is-invalid @enderror"
                                    name="device_id" placeholder="Enter device ID"
                                    value="{{ old('device_id', $expert->device_id) }}">
                                @error('device_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                        <!-- Device Type -->
                        <div class="col-lg-4 col-md-6 col-12 mb-3">
                            <label class="form-label">Device Type</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="ri-smartphone-line"></i>
                                </span>
                                <select name="device_type" class="form-select @error('device_type') is-invalid @enderror">
                                    <option selected disabled>Select device</option>
                                    <option value="android"
                                        {{ old('device_type', $expert->device_type) == 'android' ? 'selected' : '' }}>
                                        Android</option>
                                    <option value="ios"
                                        {{ old('device_type', $expert->device_type) == 'ios' ? 'selected' : '' }}>
                                        IOS</option>
                                    <!-- <option>Web</option> -->
                                </select>
                                @error('device_type')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    @endif

                    <!-- training center -->
                    <div class="col-lg-4 col-md-6 col-12 mb-3">
                        <label class="form-label">Training Center</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="ri-calendar-line"></i>
                            </span>
                            <select class="form-select @error('training_center_id') is-invalid @enderror"
                                name="training_center_id">
                                <option selected disabled>Select Training Center</option>

                                @foreach ($trainingCenters as $center)
                                    <option value="{{ $center->id }}"
                                        {{ old('training_center_id', $expert->expertDetail?->training_center_id ?? '') == $center->id ? 'selected' : '' }}>
                                        {{ $center->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('training_center_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-12 mb-3">
                        <label class="form-label">Service Location</label>

                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="ri-map-pin-line"></i>
                            </span>

                            <select class="form-select @error('service_location_id') is-invalid @enderror"
                                name="service_location_id">

                                <option selected disabled>
                                    Select Service Location
                                </option>

                                @foreach ($serviceLocations as $location)
                                    <option value="{{ $location->id }}"
                                        {{ old('service_location_id', $expert->expertDetail?->service_location_id ?? '') == $location->id ? 'selected' : '' }}>

                                        {{ $location->address }}

                                    </option>
                                @endforeach

                            </select>

                            @error('service_location_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    <!-- Status -->
                    <div class="col-lg-4 col-md-6 col-12 mb-3">
                        <label class="form-label">Status</label>

                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="ri-shield-check-line"></i>
                            </span>

                            <select class="form-select @error('status') is-invalid @enderror" name="status">
                                <option disabled>Select status</option>

                                <option value="1" {{ old('status', $expert->status) == 1 ? 'selected' : '' }}>
                                    Active
                                </option>

                                <option value="0" {{ old('status', $expert->status) == 0 ? 'selected' : '' }}>
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
                    <!--  -->

                    <!-- Status Toggle -->
                    <div class="col-lg-4 col-md-6 col-12 mb-3">
                        <label class="form-label">Is Online</label>
                        <!-- Hidden input (for OFF value) -->
                        <input type="hidden" name="is_online" value="0">
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input @error('is_online') is-invalid @enderror" type="checkbox"
                                id="statusToggle" name="is_online" value="1"
                                {{ old('is_online', $expert->expertDetail->is_online ?? '') == '1' ? 'checked' : '' }}>
                        </div>
                        @error('is_online')
                            <div class="invalid-feedback d-block">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <!-- ================= KYC ================= -->
                    <div class="col-12 mt-1">
                        <h6 class="fw-bold border-bottom pb-1 mb-2">KYC Details</h6>
                    </div>

                    <!-- Aadhar Front -->
                    <div class="col-lg-3 col-md-6 col-12 mb-3">
                        <label class="form-label">Aadhar Front</label>
                        <input type="file" name="aadhar_front" id="aadharFrontInput" class="form-control">

                        <div class="mt-2">
                            <img id="aadharFrontPreview"
                                src="{{ $expert->expertDetail?->aadhar_front ? asset($expert->expertDetail->aadhar_front) : asset('assets/img/no-image.png') }}"
                                width="70" height="70" class="rounded-circle border" style="object-fit: cover;">
                        </div>
                    </div>

                    <!-- Aadhar Back -->
                    <div class="col-lg-3 col-md-6 col-12 mb-3">
                        <label class="form-label">Aadhar Back</label>
                        <input type="file" name="aadhar_back" id="aadharBackInput" class="form-control">

                        <div class="mt-2">
                            <img id="aadharBackPreview"
                                src="{{ $expert->expertDetail?->aadhar_back ? asset($expert->expertDetail->aadhar_back) : asset('assets/img/no-image.png') }}"
                                width="70" height="70" class="rounded-circle border" style="object-fit: cover;">
                        </div>
                    </div>
                    <!-- AADHAR -->

                    <div class="col-lg-3 col-md-6 col-12 mb-3">
                        <label class="form-label">Aadhar Number</label>
                        <input type="text" name="aadhar_number" maxlength="12" pattern="\d{12}" class="form-control"
                            placeholder="Enter Aadhar Number"
                            value="{{ old('aadhar_number', $expert->expertDetail?->aadhar_number) }}">
                    </div>
                    <!-- PAN -->
                    <div class="col-lg-3 col-md-6 col-12 mb-3">
                        <label class="form-label">PAN Number</label>
                        <input type="text" name="pan_number" maxlength="10" class="form-control"
                            placeholder="ABCDE1234F" value="{{ old('pan_number', $expert->expertDetail?->pan_number) }}">
                    </div>

                    <!-- Empty (alignment ke liye optional ya future use) -->
                    <div class="col-lg-3 col-md-6 col-12 mb-3"></div>


                    <!-- ================= BANK ================= -->

                    <div class="col-12 mt-1">
                        <h6 class="fw-bold border-bottom pb-1 mb-2">Bank Details</h6>
                    </div>

                    <!-- Account Holder -->
                    <div class="col-lg-3 col-md-6 col-12 mb-3">
                        <label class="form-label">Account Holder</label>
                        <input type="text" name="account_holder_name" class="form-control"
                            placeholder="Enter Account Holder Name "
                            value="{{ old('account_holder_name', $expert->expertDetail?->account_holder_name) }}">
                    </div>

                    <!-- Account Number -->
                    <div class="col-lg-3 col-md-6 col-12 mb-3">
                        <label class="form-label">Account Number</label>
                        <input type="text" name="account_number" class="form-control"
                            placeholder="Enter Account Number"
                            value="{{ old('account_number', $expert->expertDetail?->account_number) }}">
                    </div>

                    <!-- IFSC -->
                    <div class="col-lg-3 col-md-6 col-12 mb-3">
                        <label class="form-label">IFSC</label>
                        <input type="text" name="ifsc_code" class="form-control" placeholder="SBIN0001234"
                            value="{{ old('ifsc_code', $expert->expertDetail?->ifsc_code) }}">
                    </div>

                    <!-- Bank Name -->
                    <div class="col-lg-3 col-md-6 col-12 mb-3">
                        <label class="form-label">Bank Name</label>
                        <input type="text" name="bank_name" class="form-control"
                            value="{{ old('bank_name', $expert->expertDetail?->bank_name) }}">
                    </div>
                    <!--  -->
                    @if (!$expert->exists)
                        {{-- <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                Emergency Contacts
                            </h5>
                        </div> --}}
                        <div class="col-12 mt-1">
                            <h6 class="fw-bold border-bottom pb-1 mb-2">Emergency Contacts</h6>
                        </div>
                        <div class="col-lg-4 col-md-6 col-12 mb-3">
                            <label class="form-label">Name</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="ri-user-line"></i>
                                </span>
                                <input type="text" name="emergency_contact_name"
                                    class="form-control @error('emergency_contact_name') is-invalid @enderror"
                                    placeholder="Enter user id" value="{{ old('emergency_contact_name') }}">
                                @error('emergency_contact_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <!-- Phone -->
                        <div class="col-lg-4 col-md-6 col-12 mb-3">
                            <label class="form-label">Phone</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="ri-phone-line"></i>
                                </span>
                                <input type="text" name="emergency_contact_phone"
                                    class="form-control @error('emergency_contact_phone') is-invalid @enderror"
                                    placeholder="Enter phone" value="{{ old('emergency_contact_phone') }}">
                                @error('emergency_contact_phone')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    @endif
                    <!--  -->
                </div>
                <div class="mt-3">
                    <button class="btn btn-primary">
                        <i class="ri-save-line me-1"></i>
                        {{ $expert->id ? 'Update' : 'Save' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('imageInput').addEventListener('change', function(e) {
            let file = e.target.files[0];
            if (file) {
                document.getElementById('previewImage').src = URL.createObjectURL(file);
            }
        });
    </script>
    <script>
        // Aadhar Front Preview
        document.getElementById('aadharFrontInput').addEventListener('change', function(e) {
            let file = e.target.files[0];
            if (file) {
                document.getElementById('aadharFrontPreview').src = URL.createObjectURL(file);
            }
        });

        // Aadhar Back Preview
        document.getElementById('aadharBackInput').addEventListener('change', function(e) {
            let file = e.target.files[0];
            if (file) {
                document.getElementById('aadharBackPreview').src = URL.createObjectURL(file);
            }
        });

        // PAN uppercase
        document.querySelector('input[name="pan_number"]').addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });
    </script>
@endsection
