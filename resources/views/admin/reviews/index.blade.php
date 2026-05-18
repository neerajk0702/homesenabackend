@extends('admin.layouts.master')
@section('title', 'Review Table')
@section('content')
    <div class="card">
        <!-- ALERT MESSAGE -->
        <div class="p-3">
            @include('admin.layouts.partials.alerts')
        </div>
        <!-- Header -->
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">User Reviews</h5>
            <div class="d-flex align-items-center gap-3">
                <!-- Search -->
                <form method="GET" action="{{ route('admin.reviews.index') }}" class="d-flex align-items-center">
                    <div class="d-flex align-items-center">
                        <span class="me-2">Search:</span>
                        <input name="search" type="search" class="form-control form-control-sm"
                            placeholder="Search reviews..." value="{{ request('search') }}" style="width:200px;">
                    </div>

                </form>

            </div>
        </div>
        <hr class="my-0 mb-2">

        <!-- Table -->
        <div class="table-responsive px-4 pb-3">
            <table class="table table-hover align-middle table-bordered">
                <thead class="bg-label-secondary">
                    <tr>
                        <th width="60">ID</th>
                        <th>Booking Slot</th>
                        <th>Booking</th>
                        <th>User</th>
                        <th>Expert</th>
                        <th>Rating</th>
                        <th>Review</th>
                        <th width="120">Recommend</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reviews as $review)
                        <tr>
                            <!-- ID -->
                            <td>{{ $loop->iteration }}</td>
                            <!-- Booking Slot -->
                            <td>{{ $review->booking_slot_id }}</td>
                            <!-- Booking -->
                            <td>{{ $review->booking_id ?? '-' }}</td>
                            <!-- User -->
                            <td>
                                <span class="fw-semibold">
                                    {{ $review->user->name ?? '-' }}
                                </span>
                            </td>
                            <!-- Expert -->
                            <td>
                                <span class="fw-semibold">
                                    {{ $review->expert->name ?? '-' }}
                                </span>
                            </td>

                            <!-- Rating -->
                            <td>
                                @for ($i = 1; $i <= 5; $i++)
                                    <i
                                        class="ri-star{{ $i <= $review->rating ? '-fill text-warning' : '-line text-muted' }}"></i>
                                @endfor
                            </td>

                            <!-- Review -->
                            <td style="max-width:200px;">
                                {{ \Illuminate\Support\Str::limit($review->review, 50) }}
                            </td>

                            <!-- Recommend -->
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input toggle-recommend" type="checkbox"
                                        data-id="{{ $review->id }}" style="transform: scale(1.3); cursor:pointer;"
                                        {{ $review->would_recommend ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill"
                                        data-bs-toggle="dropdown">
                                        <i class="ri-more-2-line"></i>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.reviews.edit', $review->id) }}">
                                                <i class="ri-pencil-line me-2"></i> Edit
                                            </a>
                                        </li>

                                        <li>
                                            <form method="POST"
                                                action="{{ route('admin.reviews.destroy', $review->id) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger"
                                                    onclick="return confirm('Delete service?')">
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
                            <td colspan="8" class="text-center">No reviews found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination (Dynamic) -->
        <div class="row px-4 pb-3 align-items-center">

            {{ $reviews->links('pagination::bootstrap-5') }}

        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {

                document.querySelectorAll('.toggle-recommend').forEach(function(toggle) {

                    toggle.addEventListener('change', function() {

                        let reviewId = this.getAttribute('data-id');
                        let value = this.checked ? 1 : 0;

                        //  Confirm Alert
                        let confirmAction = confirm(
                            value === 1 ?
                            "Are you sure you want to mark this as Recommended?" :
                            "Are you sure you want to remove from Recommended?"
                        );

                        if (!confirmAction) {

                            this.checked = !this.checked;
                            return;
                        }

                        fetch(`/admin/reviews/${reviewId}`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    _method: 'PUT',
                                    would_recommend: value
                                })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (!data.status) {
                                    alert('Update failed');
                                    this.checked = !value;
                                }
                            })
                            .catch(() => {
                                alert('Something went wrong');
                                this.checked = !value;
                            });

                    });

                });

            });
        </script>
    @endsection
