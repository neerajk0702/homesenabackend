@extends('admin.layouts.master')
@section('title', 'Expert Details')

@section('content')
    <div class="card">

        <!-- ALERT -->
        <div class="p-3">
            @include('admin.layouts.partials.alerts')
        </div>

        <!-- HEADER -->
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0">Expert Details</h5>

            <a href="{{ route('admin.experts.index') }}" class="btn btn-sm btn-secondary">
                <i class="ri-arrow-left-line me-1"></i> Back
            </a>
        </div>

        <hr class="my-0">

        <!-- EXPERT INFO -->
        <div class="row px-4 py-3 align-items-center">
            <div class="col-md-2 text-center mb-3 mb-md-0">
                <img src="{{ $expert->profile_image ? fileUrl($expert->profile_image) : asset('assets/img/default-profile-image.jpg') }}"
                    width="80" height="80" class="rounded-circle">
            </div>

            <div class="col-md-10">
                <div class="row">

                    <div class="col-md-3 mb-2">
                        <strong>Name:</strong>
                        {{ $expert->name ?? 'N/A' }}
                    </div>

                    <div class="col-md-3 mb-2">
                        <strong>Email:</strong>
                        {{ $expert->email ?? 'N/A' }}
                    </div>

                    <div class="col-md-3 mb-2">
                        <strong>Phone:</strong>
                        {{ $expert->phone ?? 'N/A' }}
                    </div>

                    <div class="col-md-3 mb-2">
                        <strong>Gender:</strong>
                        {{ $expert->gender ? ucfirst($expert->gender) : 'N/A' }}
                    </div>

                    <div class="col-md-3 mb-2">
                        <strong>Status:</strong>

                        {!! $expert->status
                            ? '<span class="badge bg-label-success rounded-pill">Active</span>'
                            : '<span class="badge bg-label-danger rounded-pill">Inactive</span>' !!}
                    </div>

                    <div class="col-md-3 mb-2">
                        <strong>Pan No:</strong>
                        {{ $expert->expertDetail?->pan_number ?? 'N/A' }}
                    </div>

                    <div class="col-md-3 mb-2">
                        <strong>Aadhar No:</strong>
                        {{ $expert->expertDetail?->aadhar_number ?? 'N/A' }}
                    </div>

                </div>
            </div>
        </div>

        <hr class="my-0">

        <!-- 🔥 NAV TABS -->
        <div class="px-4 pb-3">

            <ul class="nav nav-pills mb-3">

                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#rating">
                        <i class="ri-star-line me-1"></i> Rating
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#addresses">
                        <i class="ri-map-pin-line me-1"></i> Expert Addresses
                    </button>
                </li>

                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#details">
                        <i class="ri-file-list-3-line me-1"></i> Additionals Details
                    </button>
                </li>

                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#contacts">
                        <i class="ri-phone-line me-1"></i> Emergency Contacts
                    </button>
                </li>

                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#slots">
                        <i class="ri-time-line me-1"></i> Booking Slots
                    </button>
                </li>

                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#devices">
                        <i class="ri-smartphone-line me-1"></i> Expert Devices
                    </button>
                </li>

                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#logs">
                        <i class="ri-pulse-line me-1"></i> Online Logs
                    </button>
                </li>

            </ul>

            <div class="tab-content">

                <!-- RATING -->
                <div class="tab-pane fade show active" id="rating">
                    <div class="row">
                        <div class="col-md-4"><strong>Average Rating:</strong>
                            {{ optional($expert->ratingStat)->avg_rating ?? 0 }}
                        </div>
                        <div class="col-md-4"><strong>Total Reviews:</strong>
                            {{ optional($expert->ratingStat)->total_reviews ?? 0 }}</div>
                        <div class="col-md-4"><strong>Stars:</strong> @php $avg = optional($expert->ratingStat)->avg_rating ?? 0; @endphp
                            @for ($i = 1; $i <= 5; $i++)
                                @if ($i <= $avg)
                                    <span class="text-warning">★</span>
                                @else
                                    <span class="text-muted">☆</span>
                                @endif
                            @endfor
                        </div>

                    </div>
                </div>

                <!-- ADDRESSES -->
                <div class="tab-pane fade" id="addresses">
                    <div id="addresses-container">
                        @include('admin.experts.partials.addresses_tab')
                    </div>
                </div>

                <!-- DETAILS -->
                <div class="tab-pane fade" id="details">
                    <div id="details-container">
                        @include('admin.experts.partials.details_tab')
                    </div>
                </div>

                <!-- CONTACTS -->
                <div class="tab-pane fade" id="contacts">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(optional($expert->expertDetail)->emergencyContacts ?? [] as $key => $contact)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $contact->name ?? '-' }}</td>
                                    <td>{{ $contact->phone ?? '-' }}</td>
                                    <td>{{ $contact->updated_at }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">No Emergency Contacts Found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- SLOTS -->
                <div class="tab-pane fade" id="slots">
                    <div id="slots-container">
                        @include('admin.experts.partials.slots_tab')
                    </div>
                </div>

                <!-- DEVICES -->
                <div class="tab-pane fade" id="devices">
                    <div id="devices-container">
                        @include('admin.experts.partials.devices_tab')
                    </div>
                </div>

                <!-- LOGS -->
                <div class="tab-pane fade" id="logs">
                    <div id="logs-container">
                        @include('admin.experts.partials.logs_tab')
                    </div>
                </div>

            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // 1. On page load, restore the active tab from the ?tab= URL param
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab');

            if (activeTab) {
                const tabEl = document.querySelector(`[data-bs-target="#${activeTab}"]`);
                if (tabEl) {
                    bootstrap.Tab.getOrCreateInstance(tabEl).show();
                }
            }

            // 2. When a tab is clicked, sync URL and reset the tab to page 1
            document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function(tabEl) {
                tabEl.addEventListener('shown.bs.tab', function(e) {
                    const tabId = e.target.getAttribute('data-bs-target').replace('#', '');
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', tabId);

                    // Remove all pagination params from URL
                    const keysToDelete = [];
                    url.searchParams.forEach((val, key) => {
                        if (key.endsWith('_page')) keysToDelete.push(key);
                    });
                    keysToDelete.forEach(k => url.searchParams.delete(k));

                    history.replaceState(null, '', url.toString());

                    // Reset DOM to page 1 if currently on another page
                    const container = document.getElementById(`${tabId}-container`);
                    if (container) {
                        const activePageEl = container.querySelector(
                            '.page-item.active .page-link, .page-item.active span.page-link');
                        if (activePageEl && activePageEl.textContent.trim() !== '1') {
                            const fetchUrl = new URL(window.location.href);
                            fetchUrl.searchParams.set('ajax_tab', tabId);
                            fetchUrl.searchParams.set(`${tabId}_page`, 1);
                            container.style.opacity = '0.5';

                            fetch(fetchUrl.toString(), {
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                })
                                .then(r => r.text())
                                .then(html => {
                                    container.innerHTML = html;
                                    container.style.opacity = '1';
                                })
                                .catch(err => {
                                    console.error('Error fetching page 1:', err);
                                    container.style.opacity = '1';
                                });
                        }
                    }
                });
            });

            // 3. Intercept pagination links and same-page reset links inside tab panes
            document.querySelectorAll('.tab-pane').forEach(function(pane) {
                pane.addEventListener('click', function(e) {
                    const link = e.target.closest('a');
                    if (!link || !link.href) return;

                    const linkUrl = new URL(link.href);
                    const tabId = pane.getAttribute('id');
                    const container = document.getElementById(`${tabId}-container`);
                    if (!container) return;

                    const isPagination = [...linkUrl.searchParams.keys()].some(k => k.endsWith(
                        '_page'));
                    const isSamePage = linkUrl.pathname === window.location.pathname;

                    if (!isPagination && !isSamePage) return;

                    e.preventDefault();
                    linkUrl.searchParams.set('ajax_tab', tabId);
                    container.style.opacity = '0.5';

                    fetch(linkUrl.toString(), {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(r => r.text())
                        .then(html => {
                            container.innerHTML = html;
                            container.style.opacity = '1';

                            const newUrl = new URL(linkUrl.toString());
                            newUrl.searchParams.delete('ajax_tab');
                            newUrl.searchParams.set('tab', tabId);
                            history.replaceState(null, '', newUrl.toString());
                        })
                        .catch(err => {
                            console.error('Error fetching data:', err);
                            container.style.opacity = '1';
                        });
                });
            });

            // 4. Intercept filter form submissions inside tab panes via AJAX
            document.querySelectorAll('.tab-pane').forEach(function(pane) {
                pane.addEventListener('submit', function(e) {
                    const form = e.target.closest('form');
                    if (!form) return;

                    e.preventDefault();

                    const tabId = pane.getAttribute('id');
                    const container = document.getElementById(`${tabId}-container`);
                    if (!container) return;

                    const fetchUrl = new URL(form.action);
                    const formData = new FormData(form);
                    formData.forEach((value, key) => {
                        if (value) {
                            fetchUrl.searchParams.set(key, value);
                        } else {
                            fetchUrl.searchParams.delete(key);
                        }
                    });

                    fetchUrl.searchParams.set('ajax_tab', tabId);
                    container.style.opacity = '0.5';

                    fetch(fetchUrl.toString(), {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(r => r.text())
                        .then(html => {
                            container.innerHTML = html;
                            container.style.opacity = '1';

                            const newUrl = new URL(fetchUrl.toString());
                            newUrl.searchParams.delete('ajax_tab');
                            newUrl.searchParams.set('tab', tabId);
                            history.replaceState(null, '', newUrl.toString());
                        })
                        .catch(err => {
                            console.error('Error applying filter:', err);
                            container.style.opacity = '1';
                        });
                });
            });

        });
    </script>
@endpush
