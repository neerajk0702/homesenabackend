@extends('admin.layouts.master')

@section('title', 'Booking Cancel Reasons')

@section('content')

    <div class="card">

        <!-- ALERT -->
        <div class="p-3">
            @include('admin.layouts.partials.alerts')
        </div>

        <!-- Header -->
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Booking Cancel Reasons</h5>

            <div class="d-flex align-items-center gap-3">

                <!-- Search (optional future use) -->
                <form method="GET" action="{{ route('admin.booking_cancel_reasons.index') }}"
                    class="d-flex align-items-center">
                    <span class="me-2">Search:</span>
                    <input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-sm"
                        placeholder="Search reason..." style="width:200px;">
                </form>

                <!-- Add Button -->
                <a href="{{ route('admin.booking_cancel_reasons.create') }}" class="btn btn-primary btn-sm">
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
                        <th>Title</th>
                        <th width="200">Created At</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($reasons as $reason)
                        <tr>
                            <td>{{ $loop->iteration }}</td>

                            <td>
                                <span class="fw-semibold">
                                    {{ $reason->title }}
                                </span>
                            </td>

                            <td>
                                {{ $reason->created_at->format('d M Y h:i A') }} </td>

                            <td>
                                <div class="dropdown">

                                    <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill"
                                        data-bs-toggle="dropdown">
                                        <i class="ri-more-2-line"></i>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end">

                                        <!-- EDIT -->
                                        <li>
                                            <a class="dropdown-item"
                                                href="{{ route('admin.booking_cancel_reasons.edit', $reason->id) }}">
                                                <i class="ri-pencil-line me-2"></i> Edit
                                            </a>
                                        </li>

                                        <!-- DELETE -->
                                        <li>
                                            <form method="POST"
                                                action="{{ route('admin.booking_cancel_reasons.destroy', $reason->id) }}">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="dropdown-item text-danger"
                                                    onclick="return confirm('Delete this reason?')">
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
                            <td colspan="4" class="text-center">
                                No booking cancel reasons found
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>

        </div>

    </div>

@endsection
