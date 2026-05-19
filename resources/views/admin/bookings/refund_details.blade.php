@extends('admin.layouts.master')

@section('title', 'Refund Details')

@section('content')

<div class="card border-0 shadow-sm">

    <!-- Header -->
    <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
        
        <h5 class="mb-0 fw-bold">
            Refund Details
        </h5>

        <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm">
            <i class="ri-arrow-left-line me-1"></i>
            Back
        </a>

    </div>

    <!-- Body -->
    <div class="card-body p-3">

        <!-- BOOKING INFO -->
        <div class="d-flex align-items-center mt-1 mb-3">

            <div class="flex-grow-1 border-top"></div>

            <span class="px-3 fw-bold text-dark small">
                BOOKING INFO
            </span>

            <div class="flex-grow-1 border-top"></div>

        </div>

        <!-- Booking Info -->
        <div class="row g-3">

            <!-- Booking Code -->
            <div class="col-md-4">
                <div class="border rounded-3 p-3 bg-light h-100">

                    <small class="text-muted d-block mb-1">
                        Booking Code
                    </small>

                    <div class="fw-semibold text-dark">
                        {{ $slot->booking?->booking_code ?? 'N/A' }}
                    </div>

                </div>
            </div>

            <!-- Expert Name -->
            <div class="col-md-4">
                <div class="border rounded-3 p-3 bg-light h-100">

                    <small class="text-muted d-block mb-1">
                        Expert Name
                    </small>

                    <div class="fw-semibold text-dark">
                        {{ $slot->expert?->name ?? 'N/A' }}
                    </div>

                </div>
            </div>

            <!-- Booking Date -->
            <div class="col-md-4">
                <div class="border rounded-3 p-3 bg-light h-100">

                    <small class="text-muted d-block mb-1">
                        Booking Date
                    </small>

                    <div class="fw-semibold text-dark">
                        {{ \Carbon\Carbon::parse($slot->date)->format('d M Y') }}
                    </div>

                </div>
            </div>

            <!-- Slot Timing -->
            <div class="col-md-6">
                <div class="border rounded-3 p-3 h-100">

                    <small class="text-muted d-block mb-1">
                        Slot Timing
                    </small>

                    <div class="fw-semibold text-dark">
                        {{ \Carbon\Carbon::parse($slot->start_time)->format('h:i A') }}
                        -
                        {{ \Carbon\Carbon::parse($slot->end_time)->format('h:i A') }}
                    </div>

                </div>
            </div>

            <!-- Slot Amount -->
            <div class="col-md-6">
                <div class="border rounded-3 p-3 h-100 bg-label-success">

                    <small class="text-success d-block mb-1">
                        Slot Amount
                    </small>

                    <div class="fw-bold text-success fs-5">
                        <i class="ri-money-rupee-circle-line me-1"></i>
                        ₹{{ $slot->price }}
                    </div>

                </div>
            </div>

        </div>


        @if ($refund)

            <!-- REFUND INFO -->
            <div class="d-flex align-items-center mt-4 mb-3">

                <div class="flex-grow-1 border-top"></div>

                <span class="px-3 fw-bold text-dark small">
                    REFUND INFO
                </span>

                <div class="flex-grow-1 border-top"></div>

            </div>

            <!-- Refund Info -->
            <div class="row g-3">

                <!-- Refund ID -->
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100">

                        <div class="small">
                            
                            <span class="fw-bold text-dark fs-6">
                                Refund ID :
                            </span>

                            <span class="text-muted">
                                {{ $refund->refund_id ?? 'N/A' }}
                            </span>

                        </div>

                    </div>
                </div>

                <!-- Payment ID -->
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100">

                        <div class="small">

                            <span class="fw-bold text-dark fs-6">
                                Payment ID :
                            </span>

                            <span class="text-muted">
                                {{ $refund->payment_id ?? 'N/A' }}
                            </span>

                        </div>

                    </div>
                </div>

                <!-- Refund Amount -->
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 h-100 bg-label-danger">

                        <small class="text-danger d-block mb-1">
                            Refund Amount
                        </small>

                        <div class="fw-bold text-danger fs-5">
                            <i class="ri-money-rupee-circle-line me-1"></i>
                            ₹{{ $refund->amount }}
                        </div>

                    </div>
                </div>

                <!-- Refund Status -->
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 h-100">

                        <small class="text-muted d-block mb-2">
                            Refund Status
                        </small>

                        <span class="badge bg-label-success px-3 py-2">
                            {{ ucfirst($refund->status) }}
                        </span>

                    </div>
                </div>

                <!-- Refunded At -->
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 h-100">

                        <small class="text-muted d-block mb-1">
                            Refunded At
                        </small>

                        <div class="fw-semibold text-dark small">
                            {{ $refund->refunded_at
                                ? \Carbon\Carbon::parse($refund->refunded_at)->format('d M Y h:i A')
                                : 'N/A'
                            }}
                        </div>

                    </div>
                </div>

            </div>

        @endif

    </div>

</div>

@endsection