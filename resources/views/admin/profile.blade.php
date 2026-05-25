@extends('admin.layouts.master')

@section('title', 'My Profile')

@section('content')

<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">

        <!-- SUCCESS MESSAGE -->
        @if(session('success'))
            <div class="alert alert-success text-center">
                {{ session('success') }}
            </div>
        @endif

        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">

            <!-- Header -->
            <div class="position-relative text-white"
                style="background: linear-gradient(135deg, #667eea, #764ba2); padding: 60px 20px 80px;">

                <!-- Name -->
                <h4 class="fw-bold mb-1 text-center text-white">
                    {{ ucfirst($user->name) }}
                </h4>

                <div class="mx-auto mt-1"
                    style="width: 60px; height: 4px; background: linear-gradient(90deg, #fff, #5f2c82); border-radius: 2px;">
                </div>

                <!-- Avatar -->
                <div class="position-absolute top-100 start-50 translate-middle text-center">

                    @php
                        $imagePath = $user->profile_image
                            ? asset($user->profile_image)
                            : asset('default-user.png');
                    @endphp

                    <!-- Profile Image -->
                    <div class="rounded-circle border border-4 border-white shadow overflow-hidden mx-auto"
                        style="width: 110px; height: 110px; cursor: pointer;"
                        onclick="document.getElementById('imageInput').click();">

                        <img id="profileImage"
                            src="{{ $imagePath }}?v={{ time() }}"
                            class="w-100 h-100"
                            style="object-fit: cover;">
                    </div>

                    <!-- Upload Form -->
                    <form id="imageForm"
                        action="{{ route('admin.profile.update') }}"
                        method="POST"
                        enctype="multipart/form-data">
                        @csrf

                        <input type="file"
                            name="image"
                            id="imageInput"
                            class="d-none"
                            accept="image/*"
                            onchange="previewAndSubmit(event)">

                        <small class="text-primary d-block mt-2"
                            style="cursor:pointer;"
                            onclick="document.getElementById('imageInput').click();">
                            Change Image
                        </small>

                        <!-- ERROR MESSAGE -->
                        @error('image')
                            <div class="text-danger mt-2 text-center">
                                {{ $message }}
                            </div>
                        @enderror
                    </form>

                </div>

            </div>

            <!-- Body -->
            <div class="card-body mt-12 pt-5 px-4">

                <div class="px-2">
                    <div class="row g-3">

                        <!-- Name -->
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center p-3 border rounded-3 shadow-sm">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ri-user-line text-primary"></i>
                                    <small class="text-muted">Full Name</small>
                                </div>
                                <span class="fw-semibold">{{ $user->name }}</span>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center p-3 border rounded-3 shadow-sm">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ri-mail-line text-primary"></i>
                                    <small class="text-muted">Email</small>
                                </div>
                                <span class="fw-semibold">{{ $user->email }}</span>
                            </div>
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center p-3 border rounded-3 shadow-sm">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ri-phone-line text-primary"></i>
                                    <small class="text-muted">Phone</small>
                                </div>
                                <span class="fw-semibold">{{ $user->phone ?? 'N/A' }}</span>
                            </div>
                        </div>
<div class="col-md-6">
    <div class="d-flex justify-content-between align-items-center p-3 border rounded-3 shadow-sm">
        <div class="d-flex align-items-center gap-2">
            <i class="ri-user-3-line text-primary"></i>
            <small class="text-muted">Gender</small>
        </div>

        <span class="fw-semibold">
            {{ $user->gender ? ucfirst($user->gender) : 'N/A' }}
        </span>
    </div>
</div>
                        <!-- Role -->
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center p-3 border rounded-3 shadow-sm">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ri-shield-user-line text-primary"></i>
                                    <small class="text-muted">Role</small>
                                </div>
                                <span class="fw-semibold">{{ $user->role }}</span>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

        </div>

    </div>
</div>

<!-- JS -->
<script>
function previewAndSubmit(event) {
    const file = event.target.files[0];

    if (file) {

        // 2MB check (frontend UX)
        if (file.size > 2 * 1024 * 1024) {
            document.getElementById('imageInput').value = '';
            return;
        }

        const reader = new FileReader();

        reader.onload = function(e) {
            document.getElementById('profileImage').src = e.target.result;
        };

        reader.readAsDataURL(file);

        // Submit form
        setTimeout(() => {
            document.getElementById('imageForm').submit();
        }, 300);
    }
}
</script>

@endsection