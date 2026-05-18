@extends('admin.layouts.master')
@section('title', 'Payments')

@section('content')

<div class="card">

    <!-- ALERT -->
    <div class="p-3">
        @include('admin.layouts.partials.alerts')
    </div>

    <!-- HEADER + FILTER -->
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">

        <h5 class="card-title mb-0">Payments</h5>

        <form method="GET" action="{{ route('admin.payments.index') }}" 
              class="d-flex align-items-center gap-2 flex-wrap">

            <!-- SEARCH -->
            <input name="search" type="search" 
                   class="form-control form-control-sm"
                   placeholder="Payment / Order ID"
                   value="{{ request('search') }}" style="width:180px;">

            <!-- STATUS -->
            <select name="status" class="form-select form-select-sm" style="width:140px;">
                <option value="">Status</option>
                <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Success</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
            </select>

            <!-- BUTTONS -->
            <button class="btn btn-primary btn-sm" title="Search">
                <i class="ri-search-line"></i>
            </button>

            <a href="{{ route('admin.payments.index') }}" 
               class="btn btn-outline-secondary btn-sm" title="Reset">
                <i class="ri-refresh-line"></i>
            </a>

        </form>

    </div>

    <hr class="my-0 mb-2">
    <!-- TABLE -->
    <div class="table-responsive px-4 pb-3">
        <table class="table table-hover align-middle table-bordered">

            <thead class="bg-label-secondary">
                <tr>
                    <th width="60">#</th>
                    <th>Booking</th>
                    <th>Payment ID</th>
                    <th>Order ID</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Paid At</th>
                    <th width="120">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($payments as $key => $payment)
                    <tr>
                        <td>{{ $payments->firstItem() + $key }}</td>

                        <td>{{ $payment->booking_id }}</td>

                        <td>{{ $payment->gateway_payment_id ?? '-' }}</td>

                        <td>{{ $payment->gateway_order_id ?? '-' }}</td>

                        <td>₹{{ number_format($payment->amount, 2) }}</td>

                        <!-- STATUS -->
                        <td>
                            @php
                                $statusClass = match($payment->status) {
                                    'success' => 'success',
                                    'pending' => 'warning',
                                    'failed' => 'danger',
                                    default => 'secondary'
                                };
                            @endphp

                            <span class="badge bg-label-{{ $statusClass }} rounded-pill">
                                {{ ucfirst($payment->status) }}
                            </span>
                        </td>

                        <td>
                            {{ $payment->paid_at 
                                ? \Carbon\Carbon::parse($payment->paid_at)->format('d M Y h:i A') 
                                : '-' }}
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
                                           href="#">
                                            <i class="ri-eye-line me-2"></i> Details
                                        </a>
                                    </li>

                                    {{-- <li>
                                        <a class="dropdown-item"
                                           href="{{ route('admin.payments.edit', $payment->id) }}">
                                            <i class="ri-edit-line me-2"></i> Edit
                                        </a>
                                    </li> --}}

                                    <li>
                                        <form action="{{ route('admin.payments.destroy', $payment->id) }}"
                                              method="POST">
                                            @csrf
                                            @method('DELETE')

                                            <button class="dropdown-item text-danger"
                                                onclick="return confirm('Delete this payment?')">
                                                <i class="ri-delete-bin-line me-2"></i> Delete
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>

                    </tr>

                @empty
                    <tr>
                        <td colspan="8" class="text-center">No payments found</td>
                    </tr>
                @endforelse
            </tbody>

        </table>
    </div>

    <!-- PAGINATION -->
    <div class="row px-4 pb-3">
        {{ $payments->withQueryString()->links('pagination::bootstrap-5') }}
    </div>

</div>

@endsection