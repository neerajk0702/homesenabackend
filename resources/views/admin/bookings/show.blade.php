@extends('admin.layouts.master')
@section('title', 'Show Booking')
@section('content')
    <div class="card">
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
        <!-- Header -->
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Booking Details</h5>
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-sm btn-secondary">
                <i class="ri-arrow-left-line me-1"></i> Back
            </a>
        </div>
        <hr class="my-0">
        <!-- booking Info -->
        <div class="row px-4 py-3">
            <div class="col-md-10">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <strong>Booking Code:</strong> {{ $booking->booking_code }}
                    </div>
                    <div class="col-md-4 mb-2">
                        <strong>User:</strong> {{ $booking->user?->name }}
                    </div>
                    <div class="col-md-4 mb-2">
                        <strong>Service:</strong> {{ $booking->service?->name }}
                    </div>
                    <!--  -->
                    <div class="col-md-4 mb-2">
                        <strong>Address:</strong>
                        {{ $booking->address?->flat_no }},
                        {{ $booking->address?->area_name }},
                        {{ $booking->address?->landmark }},
                        {{ $booking->address?->address }}
                    </div>
                    <div class="col-md-4 mb-2">
                        <strong>Address Latitude:</strong> {{ $booking->address?->address_lat }}
                    </div>
                    <div class="col-md-4 mb-2">
                        <strong>Address Longitude:</strong> {{ $booking->address?->address_long }}
                    </div>
                    <div class="col-md-4 mb-2">
                        <strong>Booking Type:</strong> {{ $booking->type }}
                    </div>
                    <div class="col-md-4 mb-2">
                        <strong>Booking Sub Type:</strong> {{ $booking->booking_subtype ?? 'N/A' }}
                    </div>
                    <div class="col-md-4 mb-2">
                        <strong>Booking Start Date:</strong>
                        {{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y') }}
                    </div>
                    <div class="col-md-4 mb-2">
                        <strong>Booking End Date:</strong> {{ \Carbon\Carbon::parse($booking->end_date)->format('d M Y') }}
                    </div>
                    <div class="col-md-4 mb-2">
                        <strong>Booking Start Time:</strong> {{ \Carbon\Carbon::parse($booking->time)->format('h:i A') }}
                    </div>

                    <div class="col-md-4 mb-2">
                        <strong>Total Amount:</strong> {{ $booking->total_price }}
                    </div>

                    <div class="col-md-4 mb-2">
                        <strong>Payment Status:</strong> {{ $booking->payment_status }}
                    </div>
                    @if (!empty($booking->cancel_reason))
                        <div class="col-md-4 mb-2">
                            <strong>Cancel Reason:</strong> {{ $booking->cancel_reason }}
                        </div>
                    @endif
                    @if (!empty($booking->cancelled_at))
                        <div class="col-md-4 mb-2">
                            <strong>Cancel At:</strong> {{ $booking->cancelled_at }}
                        </div>
                    @endif
                    <div class="col-md-4 mb-2">
                        <strong>Status:</strong>

                        <span class="badge rounded-pill bg-label-{{ statusColor($booking->status) }}">
                            {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
                        </span>
                    </div>
                    <div class=" mb-2">
                        <strong>Booking Created Date/Time:</strong>
                        {{ \Carbon\Carbon::parse($booking->created_at)->format('d M Y h:i A') }}
                    </div>
                </div>
            </div>
            <hr class="my-0">

            <!-- Address Table -->
            <div class="card-header">
                <h6 class="mb-0">Booking Slots</h6>
            </div>
            <div class="table-responsive px-4 pb-3">
                <table class="table table-hover align-middle table-bordered">
                    <thead class="bg-label-secondary">
                        <tr>
                            <th width="60">ID</th>
                            <th>Expert Name</th>
                            <th>Date</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Duration</th>
                            <th>Otp</th>
                            <th>Amount</th>
                            <th>Check In Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($slots as $slot)
                            <tr>
                                <td>{{ $slots->firstItem() + $loop->index }}</td>
                                <td>
                                    <span class="fw-semibold">{{ $slot->expert?->name ?? 'N/A' }}</span>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($slot->date)->format('d M Y') }}</td>
                                <td> {{ \Carbon\Carbon::parse($slot->start_time)->format(' h:i A') }} </td>
                                <td> {{ \Carbon\Carbon::parse($slot->end_time)->format(' h:i A') }} </td>
                                <td>{{ $slot->duration }}</td>
                                <td>{{ $slot->otp_code }}</td>
                                <td>{{ $slot->price }}</td>
                                <td>{{ \Carbon\Carbon::parse($slot->check_in_time)->format('d M Y h:i A') }}</td>
                                <td>
                                    <span class="badge rounded-pill bg-label-{{ statusColor($slot->status) }}">
                                        {{ ucfirst(str_replace('_', ' ', $slot->status)) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill"
                                            data-bs-toggle="dropdown">
                                            <i class="ri-more-2-line"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <!-- refund -->
                                            <li>
                                                <form action="{{ route('admin.refund.process', $slot->id) }}" method="POST">
                                                    @csrf

                                                    <button type="submit" class="dropdown-item">
                                                        <i class="ri-user-add-line me-2"></i>
                                                        Refund Slot
                                                    </button>
                                                </form>
                                            </li>
                                            <!-- Assign Expert -->
                                            <li>
                                                <a class="dropdown-item"
                                                    href="{{ route('admin.bookings.assignExpertPage', $slot->id) }}">
                                                    <i class="ri-user-add-line me-2"></i>
                                                    Assign Expert
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item"
                                                    href="{{ route('admin.bookings.slot_logs', $slot->id) }}">
                                                    <i class="ri-file-list-3-line me-2"></i>
                                                    Slot Logs
                                                </a>
                                            </li>

                                            <li>
                                                <a class="dropdown-item"
                                                    href="{{ route('admin.bookings.slot_notifications', $slot->id) }}">
                                                    <i class="ri-notification-3-line me-2"></i>
                                                    Slot Notifications
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No booking slot found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination (Dynamic) -->
            <div class="row px-4 pb-3 align-items-center">
                {{ $slots->links('pagination::bootstrap-5') }}
            </div>
            <!-- end booking slot -->
        </div>
@endsection