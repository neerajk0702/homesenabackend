@extends('admin.layouts.master')
@section('title', 'Service Table')
@section('content')
    <div class="card">
        <!-- ALERT MESSAGE -->
        <div class="p-3">
            @include('admin.layouts.partials.alerts')
        </div>
        <!-- Header -->
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Services</h5>
            <div class="d-flex align-items-center gap-3">
                <!-- Search -->
                <form method="GET" action="{{ route('admin.services.index') }}"
                    class="d-flex align-items-center gap-2 flex-wrap">

                    <!-- 🔍 Search -->
                    <div class="d-flex align-items-center">
                        <span class="me-2">Search:</span>
                        <input name="search" type="search" class="form-control form-control-sm"
                            placeholder="Search services..." value="{{ request('search') }}" style="width:180px;">
                    </div>

                    <!-- 🔥 Status Filter -->
                    <select name="status" class="form-select form-select-sm" style="width:130px;">
                        <option value="">Status</option>
                        <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>

                    <!-- 🔍 Search Button -->
                    <button class="btn btn-primary btn-sm">
                        <i class="ri-search-line"></i>
                    </button>

                    <!-- 🔄 Reset Button -->
                    <a href="{{ route('admin.services.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="ri-refresh-line"></i>
                    </a>

                </form>
                <!-- Add -->
                <a href="{{ route('admin.services.create') }}" class="btn btn-primary btn-sm">
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
                        <th width="120">Image</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th width="120">Status</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($services as $service)
                        <tr>

                            <!-- Pagination ID -->
                            <td>
                                {{ $loop->iteration + ($services->currentPage() - 1) * $services->perPage() }}
                            </td>
                            <td>
                                @if ($service->image)
                                    <img src="{{ asset($service->image) }}" class="rounded-circle border shadow-sm"
                                        style="width:35px; height:35px; object-fit:cover;">
                                @else
                                    <img src="https://via.placeholder.com/60" class="rounded-circle border shadow-sm"
                                        style="width:35px; height:35px; object-fit:cover;">
                                @endif
                            </td>

                            <td>
                                <span class="fw-semibold">{{ $service->name }}</span>
                            </td>

                            <td style="max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                {{ \Illuminate\Support\Str::limit(strip_tags($service->description), 60) }}
                            </td>



                            <!--  STATUS TOGGLE (FIXED) -->
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input toggle-status" type="checkbox"
                                        data-id="{{ $service->id }}" style="transform: scale(1.3); cursor:pointer;"
                                        {{ $service->status == 1 ? 'checked' : '' }}>
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
                                                href="{{ route('admin.services.edit', $service->id) }}">
                                                <i class="ri-pencil-line me-2"></i> Edit
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item"
                                                href="{{ route('admin.services.show', $service->id) }}">
                                                <i class="ri-eye-line me-2"></i>
                                                Details
                                            </a>
                                        </li>
                                        <li>
                                            <form method="POST"
                                                action="{{ route('admin.services.destroy', $service->id) }}">
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
                            <td colspan="6" class="text-center">No services found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination (Dynamic) -->
        <div class="row px-4 pb-3 align-items-center">


            {{ $services->links('pagination::bootstrap-5') }}


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
                            "Are you sure you want to activate this service?" :
                            "Are you sure you want to deactivate this service?"
                        );

                        if (!confirmAction) {
                            checkbox.checked = !checkbox.checked;
                            return;
                        }

                        fetch(`/admin/services/${id}`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    _method: 'PUT',
                                    status: value
                                })
                            })
                            .then(response => response.json())
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
