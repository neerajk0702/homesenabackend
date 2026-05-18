@extends('admin.layouts.master')

@section('title', 'Instant Booking')

@section('content')
    <div class="card">
        <!-- ALERT -->
        <div class="p-3">
            @include('admin.layouts.partials.alerts')
        </div>
        <!-- Header -->
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Instant Booking Plans</h5>
            <div class="d-flex align-items-center gap-3">
                <!-- Search -->
                <form method="GET" action="{{ route('admin.instant_bookings.index') }}" class="d-flex align-items-center">
                    <span class="me-2">Search:</span>
                    <input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-sm"
                        placeholder="Search duration / price" style="width:200px;">
                </form>
                <!-- Add Button -->
                <a href="{{ route('admin.instant_bookings.create') }}" class="btn btn-primary btn-sm">
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
                        <th>Duration</th>
                        <th>Price</th>
                        <th>Discount Price</th>
                        <th>Created AT</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($settings as $item)
                        <tr>
                            <td>{{ $settings->firstItem() + $loop->index }}</td>

                            <td>
                                <span class="badge bg-label-primary">
                                    {{ $item->duration_minutes }} min
                                </span>
                            </td>

                            <td><span class=" fw-semibold">
                                    ₹ {{ $item->price }}
                                </span></td>

                            <td>
                                @if ($item->discount_price)
                                    <span class=" fw-semibold">
                                        ₹ {{ $item->discount_price }}
                                    </span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>{{ $item->created_at }}</td>
                            {{-- Actions --}}
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill"
                                        data-bs-toggle="dropdown">
                                        <i class="ri-more-2-line"></i>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end">

                                        <li>
                                            <a class="dropdown-item"
                                                href="{{ route('admin.instant_bookings.edit', $item->id) }}">
                                                <i class="ri-edit-line me-2"></i>
                                                Edit
                                            </a>
                                        </li>

                                        <li>
                                            <form action="{{ route('admin.instant_bookings.destroy', $item->id) }}"
                                                method="POST" onsubmit="return confirm('Are you sure?')">
                                                @csrf
                                                @method('DELETE')

                                                <button class="dropdown-item text-danger">
                                                    <i class="ri-delete-bin-line me-2"></i>
                                                    Delete
                                                </button>
                                            </form>
                                        </li>

                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">No Instant Booking Plans Found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="row px-4 pb-3 align-items-center">
            {{ $settings->links('pagination::bootstrap-5') }}
        </div>

    @endsection
