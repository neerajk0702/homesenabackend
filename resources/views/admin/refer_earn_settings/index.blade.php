@extends('admin.layouts.master')

@section('title', 'Refer & Earn Settings')

@section('content')

    <div class="card">

        <!-- ALERT -->
        <div class="p-3">
            @include('admin.layouts.partials.alerts')
        </div>

        <!-- Header -->
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Refer & Earn Settings</h5>

            <div class="d-flex align-items-center gap-3">

                <!-- Search -->
                <form method="GET" action="{{ route('admin.refer_earn_settings.index') }}" class="d-flex align-items-center">
                    <span class="me-2">Search:</span>
                    <input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-sm"
                        placeholder="Search coins..." style="width:200px;">
                </form>

                <!-- Add -->
                @if ($settings->count() == 0)
                    <a href="{{ route('admin.refer_earn_settings.create') }}" class="btn btn-primary btn-sm">
                        <i class="ri-add-line me-1"></i> Add
                    </a>
                @endif

            </div>
        </div>

        <hr class="my-0 mb-2">

        <!-- Table -->
        <div class="table-responsive px-4 pb-3">
            <table class="table table-hover align-middle table-bordered">
                <thead class="bg-label-secondary">
                    <tr>
                        <th width="60">ID</th>
                        <th>You Get 🪙</th>
                        <th>They Get 🪙</th>
                        <th>Date & Time</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($settings as $item)
                        <tr>

                            <!-- ID -->
                            <td>{{ $loop->iteration }}</td>

                            <!-- YOU GET -->
                            <td class="fw-semibold">
                                {{ $item->referral_amount }} Coins
                            </td>

                            <!-- THEY GET -->
                            <td class="fw-semibold">
                                {{ $item->signup_bonus }} Coins
                            </td>

                            <!-- DATE -->
                            <td>
                                {{ $item->created_at->format('d M Y, h:i A') }}
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
                                            <a class="dropdown-item"
                                                href="{{ route('admin.refer_earn_settings.edit', $item->id) }}">
                                                <i class="ri-pencil-line me-2"></i> Edit
                                            </a>
                                        </li>

                                        <li>
                                            <form method="POST"
                                                action="{{ route('admin.refer_earn_settings.destroy', $item->id) }}">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="dropdown-item text-danger"
                                                    onclick="return confirm('Delete this setting?')">
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
                            <td colspan="5" class="text-center">No Data Found</td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

    </div>

@endsection
