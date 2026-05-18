@extends('admin.layouts.master')
@section('title', 'Service Variants')

@section('content')
    <div class="card">
        <!-- ALERT MESSAGE -->
        <div class="p-3">
            @include('admin.layouts.partials.alerts')
        </div>
        <!-- Header -->
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Service Variants</h5>
            <div class="d-flex align-items-center gap-3">
                <!-- Search -->
                <form method="GET" action="{{ route('admin.service_variants.index') }}"
                    class="d-flex align-items-center gap-2 flex-wrap">

                    <!-- Search -->
                    <div class="d-flex align-items-center">
                        <span class="me-2">Search:</span>
                        <input name="search" type="search" class="form-control form-control-sm"
                            placeholder="Search Variants..." value="{{ request('search') }}" style="width:180px;">
                    </div>

                    <!-- Status Filter -->
                    <select name="status" class="form-select form-select-sm" style="width:130px;">
                        <option value="">Status</option>
                        <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>

                    <!-- Duration Filter -->
                    <select name="duration" class="form-select form-select-sm" style="width:150px;">
                        <option value="">Duration</option>
                        <option value="30" {{ request('duration') == '30' ? 'selected' : '' }}>30 Min</option>
                        <option value="60" {{ request('duration') == '60' ? 'selected' : '' }}>60 Min</option>
                        <option value="90" {{ request('duration') == '90' ? 'selected' : '' }}>90 Min</option>
                        <option value="120" {{ request('duration') == '120' ? 'selected' : '' }}>120 Min</option>
                        <option value="150" {{ request('duration') == '150' ? 'selected' : '' }}>150 Min</option>
                    </select>

                    <!-- Search Button -->
                    <button class="btn btn-primary btn-sm">
                        <i class="ri-search-line"></i>
                    </button>

                    <!-- Reset -->
                    <a href="{{ route('admin.service_variants.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="ri-refresh-line"></i>
                    </a>

                </form>
                <!-- Add -->
                <a href="{{ route('admin.service_variants.create') }}"class="btn btn-primary btn-sm">
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
                        <th>Service</th>
                        <th>Duration</th>
                        <th>Price</th>
                        <th>Discount</th>
                        <th width="120">Status</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($variants as $variant)
                        <tr>

                            <td>
                                {{ $loop->iteration + ($variants->currentPage() - 1) * $variants->perPage() }}
                            </td>

                            <td>
                                <span class="fw-semibold">
                                    {{ $variant->service->name ?? '-' }}
                                </span>
                            </td>

                            <td>{{ $variant->duration_minutes }} min</td>

                            <td>₹ {{ number_format($variant->price, 2) }}</td>

                            <td>
                                {{ $variant->discount_price ? '₹ ' . number_format($variant->discount_price, 2) : '-' }}
                            </td>

                            <!--  STATUS TOGGLE -->
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input toggle-status" type="checkbox"
                                        data-id="{{ $variant->id }}" style="transform: scale(1.3); cursor:pointer;"
                                        {{ $variant->is_active == 1 ? 'checked' : '' }}>
                                </div>
                            </td>

                            <!-- Actions -->
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill"
                                        data-bs-toggle="dropdown">
                                        <i class="ri-more-2-line"></i>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end">

                                        <li>
                                            <a class="dropdown-item"
                                                href="{{ route('admin.service_variants.edit', $variant->id) }}">
                                                <i class="ri-pencil-line me-2"></i> Edit
                                            </a>
                                        </li>

                                        <li>
                                            <form method="POST"
                                                action="{{ route('admin.service_variants.destroy', $variant->id) }}">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="dropdown-item text-danger"
                                                    onclick="return confirm('Delete this variant?')">
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
                            <td colspan="7" class="text-center">No variants found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination (Dynamic) -->
        <div class="row px-4 pb-3 align-items-center">


            {{ $variants->links('pagination::bootstrap-5') }}


        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {

                document.querySelectorAll('.toggle-status').forEach(function(toggle) {

                    toggle.addEventListener('change', function() {

                        let id = this.dataset.id;
                        let value = this.checked ? 1 : 0;
                        let checkbox = this;

                        let confirmAction = confirm(
                            value === 1 ?
                            "Are you sure you want to activate this service variant?" :
                            "Are you sure you want to deactivate this service variant?"
                        );

                        if (!confirmAction) {
                            checkbox.checked = !checkbox.checked;
                            return;
                        }

                        fetch(`/admin/service_variants/${id}`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    _method: 'PUT',
                                    is_active: value
                                })
                            })
                            .then(res => res.json())
                            .then(data => {

                                if (!data.status) {
                                    alert('Update failed');
                                    checkbox.checked = !value;
                                }

                            })
                            .catch(() => {
                                alert('Something went wrong');
                                checkbox.checked = !value;
                            });

                    });

                });

            });
        </script>
    @endsection
