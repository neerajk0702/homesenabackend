@extends('admin.layouts.master')
@section('title', 'Expert SOS')

@section('content')

    <div class="card">

        <!-- ALERT -->
        <div class="p-3">
            @include('admin.layouts.partials.alerts')
        </div>

        <!-- Header -->
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Expert SOS</h5>

            <div class="d-flex align-items-center gap-3">

                <!-- Search -->
                <form method="GET" action="{{ route('admin.expert_sos.index') }}" class="d-flex align-items-center">
                    <span class="me-2">Search:</span>
                    <input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-sm"
                        placeholder="Search expert/message..." style="width:200px;">
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
                        <th>Expert</th>
                        <th>Message</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($sos as $key => $item)
                        <tr>

                            <!-- ID -->
                            <td>{{ $sos->firstItem() + $key }}</td>

                            <!-- Expert -->
                            <td>
                                {{ $item->expert->name ?? 'N/A' }} <br>
                                <small class="text-muted">{{ $item->expert->phone ?? '' }}</small>
                            </td>

                            <!-- Message -->
                            <td>
                                {{ \Illuminate\Support\Str::limit($item->message, 50) }}
                            </td>

                            <!-- Location -->
                            <td>
                                @if ($item->latitude && $item->longitude)
                                    <a target="_blank"
                                        href="https://www.google.com/maps?q={{ $item->latitude }},{{ $item->longitude }}">
                                        View Map
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>

                            <!-- Status Dropdown (ENUM) -->
                            <td>
                                <select class="form-select form-select-sm status-change" data-id="{{ $item->id }}">

                                    <option value="pending" {{ $item->status == 'pending' ? 'selected' : '' }}>
                                        Pending
                                    </option>

                                    <option value="in_progress" {{ $item->status == 'in_progress' ? 'selected' : '' }}>
                                        In Progress
                                    </option>

                                    <option value="resolved" {{ $item->status == 'resolved' ? 'selected' : '' }}>
                                        Resolved
                                    </option>

                                </select>
                            </td>

                            <!-- Time -->
                            <td>
                                {{ optional($item->created_at)->format('d M Y, h:i A') ?? '-' }}
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
                                                href="{{ route('admin.expert_sos.show', $item->id) }}">
                                                <i class="ri-eye-line me-2"></i> Details
                                            </a>
                                        </li>
                                        <!-- 📞 Call -->
                                        <li>
                                            <a class="dropdown-item text-success" href="#">
                                                <i class="ri-phone-line me-2"></i> Call Expert
                                            </a>
                                        </li>
                                        <li>
                                            <form method="POST"
                                                action="{{ route('admin.expert_sos.destroy', $item->id) }}">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="dropdown-item text-danger"
                                                    onclick="return confirm('Delete SOS?')">
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
                            <td colspan="7" class="text-center">No SOS Found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="row px-4 pb-3 align-items-center">
            {{ $sos->links('pagination::bootstrap-5') }}
        </div>

    </div>

    <!--  Status Change Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            document.querySelectorAll('.status-change').forEach(function(select) {

                //  store old value initially
                select.setAttribute('data-old', select.value);

                select.addEventListener('change', function() {

                    let id = this.dataset.id;
                    let newValue = this.value;
                    let oldValue = this.getAttribute('data-old');
                    let el = this;

                    //  CONFIRM BEFORE CHANGE
                    if (!confirm("Are you sure you want to change status?")) {
                        el.value = oldValue; // revert
                        return;
                    }

                    el.disabled = true;

                    fetch(`/admin/expert_sos/${id}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                _method: 'PUT',
                                status: newValue
                            })
                        })
                        .then(res => res.json())
                        .then(data => {

                            el.disabled = false;

                            if (data.status) {

                                //  save new value
                                el.setAttribute('data-old', newValue);

                                showAlert('success', data.message);

                            } else {
                                el.value = oldValue;
                                showAlert('danger', 'Update failed');
                            }

                        })
                        .catch(() => {

                            el.disabled = false;
                            el.value = oldValue;

                            showAlert('danger', 'Something went wrong');
                        });

                });

            });

            //  Custom Alert
            function showAlert(type, message) {

                let alertBox = document.createElement('div');

                alertBox.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
                alertBox.style.top = '20px';
                alertBox.style.right = '20px';
                alertBox.style.zIndex = '9999';
                alertBox.style.minWidth = '250px';

                alertBox.innerHTML = `
                    <strong>${type === 'success' ? 'Success!' : 'Error!'}</strong> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;

                document.body.appendChild(alertBox);

                // auto remove
                setTimeout(() => {
                    alertBox.remove();
                }, 3000);
            }

        });
    </script>
@endsection
