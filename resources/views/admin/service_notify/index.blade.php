@extends('admin.layouts.master')
@section('title', 'Service Notify Requests')

@section('content')

    <div class="card">

        <!-- ALERT -->
        <div class="p-3">
            @include('admin.layouts.partials.alerts')
        </div>

        <!-- HEADER + FILTER -->
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">

            <h5 class="card-title mb-0">Service Notify Requests</h5>

            <form method="GET" action="{{ route('admin.service_notify.index') }}"
                class="d-flex align-items-center gap-2 flex-wrap">

                <!-- SEARCH (User / Address) -->
                <input name="search" type="search" class="form-control form-control-sm" placeholder="User / Address"
                    value="{{ request('search') }}" style="width:180px;">

                <!-- STATUS -->
                <select name="notify" class="form-select form-select-sm" style="width:140px;">
                    <option value="">Notify</option>
                    <option value="1" {{ request('notify') == '1' ? 'selected' : '' }}>Notified</option>
                    <option value="0" {{ request('notify') == '0' ? 'selected' : '' }}>Pending</option>
                </select>

                <!-- BUTTONS -->
                <button class="btn btn-primary btn-sm">
                    <i class="ri-search-line"></i>
                </button>

                <a href="{{ route('admin.service_notify.index') }}" class="btn btn-outline-secondary btn-sm">
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
                        <th>User</th>
                        <th>Latitude</th>
                        <th>Longitude</th>
                        <th>Address</th>
                        <th>Notify</th>
                        <th>Created At</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($requests as $key => $request)
                        <tr>
                            <td>{{ $requests->firstItem() + $key }}</td>

                            <!-- USER -->
                            <td>
                                {{ $request->user->name ?? 'N/A' }} <br>
                                <small class="text-muted">
                                    {{ $request->user->email ?? '' }}
                                </small>
                            </td>

                            <td>{{ $request->latitude }}</td>
                            <td>{{ $request->longitude }}</td>

                            <!-- ADDRESS -->
                            <td>{{ $request->address }}</td>

                            <!-- Notify -->
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input toggle-notify" type="checkbox"
                                        data-id="{{ $request->id }}" style="transform: scale(1.3); cursor:pointer;"
                                        {{ $request->notify == 1 ? 'checked' : '' }}>
                                </div>
                            </td>

                            <!-- DATE -->
                            <td>
                                {{ $request->created_at->format('d M Y h:i A') }}
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
                                            <form action="{{ route('admin.service_notify.destroy', $request->id) }}"
                                                method="POST">
                                                @csrf
                                                @method('DELETE')

                                                <button class="dropdown-item text-danger"
                                                    onclick="return confirm('Delete this request?')">
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
                            <td colspan="7" class="text-center">No requests found</td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        <!-- PAGINATION -->
        <div class="row px-4 pb-3">
            {{ $requests->withQueryString()->links('pagination::bootstrap-5') }}
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            document.querySelectorAll('.toggle-notify').forEach(function(toggle) {

                toggle.addEventListener('change', function() {

                    let id = this.dataset.id;
                    let value = this.checked ? 1 : 0;

                    let confirmAction = confirm(
                        value === 1 ?
                        "Mark as Notified?" :
                        "Mark as Pending?"
                    );

                    if (!confirmAction) {
                        this.checked = !this.checked;
                        return;
                    }

                    fetch(`/admin/service_notify/${id}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                _method: 'PUT',
                                notify: value
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
