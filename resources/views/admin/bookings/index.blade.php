@extends('admin.layouts.master')
@section('title', 'Bookings')
@section('content')
    <div class="card">
        <!-- ALERT MESSAGE -->
        <div class="p-3">
            @include('admin.layouts.partials.alerts')
        </div>
        <!-- Header -->
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Bookings</h5>
            <div class="d-flex align-items-center gap-3">
                <!-- Search -->
                <form method="GET" action="{{ route('admin.bookings.index') }}"
                    class="d-flex align-items-center gap-2 flex-wrap">

                    <!--  Search -->
                    <div class="d-flex align-items-center">
                        <span class="me-2">Search:</span>
                        <input name="search" type="search" class="form-control form-control-sm"
                            placeholder="Search booking..." value="{{ request('search') }}" style="width:180px;">
                    </div>

                    <!--  Type -->
                    <select name="type" class="form-select form-select-sm" style="width:140px;">
                        <option value="">Type</option>
                        <option value="scheduled" {{ request('type') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                        <option value="instant" {{ request('type') == 'instant' ? 'selected' : '' }}>Instant</option>
                    </select>

                    <!--  Sub Type -->
                    <select name="sub_type" class="form-select form-select-sm" style="width:150px;">
                        <option value="">Sub Type</option>
                        <option value="recurring" {{ request('sub_type') == 'recurring' ? 'selected' : '' }}>Recurring
                        </option>
                        <option value="single" {{ request('sub_type') == 'single' ? 'selected' : '' }}>Single</option>
                    </select>

                    <!--  Status -->
                    <select name="status" class="form-select form-select-sm" style="width:160px;">
                        <option value="">Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed
                        </option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled
                        </option>
                        <option value="complete" {{ request('status') == 'complete' ? 'selected' : '' }}>Complete</option>
                    </select>

                    <!-- Button -->
                    <button class="btn btn-primary btn-sm">
                        <i class="ri-search-line"></i>
                    </button>

                    <!--  Reset -->
                    <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="ri-refresh-line"></i>
                    </a>

                </form>
            </div>
        </div>
        <hr class="my-0 mb-2">
      
        <!-- php function -->
        @php
            function statusColor($status)
            {
                return match ($status) {
                    'pending' => 'warning',
                    'accepted' => 'info',
                    'on_the_way' => 'primary',
                    'ongoing' => 'dark',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                    'partial' => 'secondary',
                    default => 'light',
                };
            }
        @endphp
        <!-- Table -->
        <div class="table-responsive px-4 pb-3">
            <table class="table table-hover align-middle table-bordered">
                <thead class="bg-label-secondary">
                    <tr>
                        <th width="60">ID</th>
                        <th>Booking Code</th>
                        <th>Booking Type</th>
                        <th>Booking Sub Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Total Amount</th>
                        <th width="120">Status</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        <tr>
                            <td>{{ $bookings->firstItem() + $loop->index }}</td>
                            <td>
                                <span class="fw-semibold">{{ $booking->booking_code }}</span>
                            </td>
                            <td>{{ $booking->type }}</td>
                            <td>{{ $booking->booking_subtype ?? 'N/A' }}</td>
                            <td>{{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($booking->end_date)->format('d M Y') }}</td>
                            <td>{{ $booking->total_price }}</td>
                            <td>
                                <span class="badge rounded-pill bg-label-{{ statusColor($booking->status) }}">
                                    {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
                                </span>
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
                                                href="{{ route('admin.bookings.show', $booking->id) }}">
                                                <i class="ri-eye-line me-2"></i>
                                                Details
                                            </a>
                                        </li>
                                        <li>


                                            <a class="dropdown-item"
                                                href="{{ route('admin.download.invoice', $booking->id) }}">
                                                <i class="ri-file-pdf-line me-2"></i>
                                                Download Invoice
                                            </a>

                                        </li>

                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No booking found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination (Dynamic) -->
        <div class="row px-4 pb-3 align-items-center">
            {{ $bookings->links('pagination::bootstrap-5') }}
        </div>

    @endsection
