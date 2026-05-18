@extends('admin.layouts.master')
@section('title', 'Home Promotion Table')
@section('content')
    <div class="card">
        <!-- ALERT MESSAGE -->
        <div class="p-3">
            @include('admin.layouts.partials.alerts')
        </div>
        <!-- Header -->
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Home Promotion</h5>
            <div class="d-flex align-items-center gap-3">
                <!-- Search -->
                <form method="GET" action="{{ route('admin.home_promotion.index') }}"
                    class="d-flex align-items-center gap-2 flex-wrap">

                    <!-- Search -->
                    <div class="d-flex align-items-center">
                        <span class="me-2">Search:</span>
                        <input name="search" type="search" class="form-control form-control-sm"
                            placeholder="Search promotions..." value="{{ request('search') }}" style="width:180px;">
                    </div>

                    <!-- Status Filter -->
                    <select name="status" class="form-select form-select-sm" style="width:130px;">
                        <option value="">Status</option>
                        <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>

                    <!-- Search Button -->
                    <button class="btn btn-primary btn-sm">
                        <i class="ri-search-line"></i>
                    </button>

                    <!-- Reset -->
                    <a href="{{ route('admin.home_promotion.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="ri-refresh-line"></i>
                    </a>

                </form>
                <!-- Add -->
                <a href="{{ route('admin.home_promotion.create') }}" class="btn btn-primary btn-sm">
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
                        <th>Image</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Date & Time</th>
                        <th width="120">Status</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($promotions as $promotion)
                        <tr>

                            <!-- ID -->
                            <td>{{ $loop->iteration }}</td>

                            <!-- IMAGE -->
                            <td>
                                @if ($promotion->image)
                                    <img src="{{ asset($promotion->image) }}" width="50" height="50" class="rounded">
                                @else
                                    <span class="text-muted">No Image</span>
                                @endif
                            </td>

                            <!-- TITLE -->
                            <td>{{ $promotion->title }}</td>

                            <!-- DESCRIPTION -->
                            <td style="max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                {{ \Illuminate\Support\Str::limit(strip_tags($promotion->description), 60) }}
                            </td>
                            <!-- DATE -->
                            <!-- <td>
                                {{ $promotion->promotion_datetime
                                    ? \Carbon\Carbon::parse($promotion->promotion_datetime)->format('d M Y, h:i A')
                                    : '-' }}
                            </td> -->
                            <td>
                                @if ($promotion->promotion_datetime)
                                    @php
                                        $promotionDate = \Carbon\Carbon::parse($promotion->promotion_datetime);
                                    @endphp
                                    {{ $promotionDate->format('d M Y, h:i A') }}
                                    @if ($promotionDate->isPast())
                                        <span class="text-danger ms-2">
                                            (Expired)
                                        </span>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>

                            <!-- STATUS TOGGLE -->

                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input toggle-status" type="checkbox"
                                        data-id="{{ $promotion->id }}" style="transform: scale(1.3); cursor:pointer;"
                                        {{ $promotion->status == 1 ? 'checked' : '' }}>
                                </div>
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
                                                href="{{ route('admin.home_promotion.edit', $promotion->id) }}">
                                                <i class="ri-pencil-line me-2"></i> Edit
                                            </a>
                                        </li>

                                        <li>
                                            <form method="POST"
                                                action="{{ route('admin.home_promotion.destroy', $promotion->id) }}">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="dropdown-item text-danger"
                                                    onclick="return confirm('Delete item?')">
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
                            <td colspan="7" class="text-center">No Data Found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination (Dynamic) -->
        <div class="row px-4 pb-3 align-items-center">
            {{ $promotions->links('pagination::bootstrap-5') }}
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {

                document.querySelectorAll('.toggle-status').forEach(function(toggle) {

                    toggle.addEventListener('change', function() {

                        let id = this.getAttribute('data-id');
                        let value = this.checked ? 1 : 0;

                        //  Confirm Alert
                        let confirmAction = confirm(
                            value === 1 ?
                            "Are you sure you want to activate this?" :
                            "Are you sure you want to inactivate this?"
                        );

                        if (!confirmAction) {
                            this.checked = !this.checked;
                            return;
                        }

                        fetch(`/admin/home_promotion/${id}`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    _method: 'PUT',
                                    status: value
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
