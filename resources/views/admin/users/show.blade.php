@extends('admin.layouts.master')
@section('title', 'User Details')

@section('content')
    <div class="card">

        <!-- ALERT -->
        <div class="p-3">
            @include('admin.layouts.partials.alerts')
        </div>

        <!-- HEADER -->
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0">User Details</h5>

            <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-secondary">
                <i class="ri-arrow-left-line me-1"></i> Back
            </a>
        </div>

        <hr class="my-0">

        <!-- USER INFO -->
        <div class="row px-4 py-3 align-items-center">
            <div class="col-md-2 text-center mb-3 mb-md-0">
                <img src="{{ $user->profile_image ? fileUrl($user->profile_image) : asset('assets/img/default-profile-image.jpg') }}"
                    width="80" height="80" class="rounded-circle">
            </div>

            <div class="col-md-10">
                <div class="row">
                    <div class="col-md-4 mb-2"><strong>Name:</strong> {{ $user->name }}</div>
                    <div class="col-md-4 mb-2"><strong>Email:</strong> {{ $user->email }}</div>
                    <div class="col-md-4 mb-2"><strong>Phone:</strong> {{ $user->phone ?? 'N/A' }}</div>
                    <div class="col-md-4 mb-2"><strong>Gender:</strong>{{ $user->gender ? ucfirst($user->gender) : 'N/A' }}</div>

                    <div class="col-12 col-sm-6 col-md-4 mb-2">
                        <strong>Profile Completed:</strong>
                        @if ($user->profile_completed === 1)
                            <span class="badge bg-label-primary rounded-pill">Yes</span>
                        @else
                            <span class="badge bg-label-secondary rounded-pill">No</span>
                        @endif
                    </div>

                    <div class="col-md-4 mb-2">
                        <strong>Status:</strong>
                        {!! $user->status
                            ? '<span class="badge bg-label-success rounded-pill">Active</span>'
                            : '<span class="badge bg-label-danger rounded-pill">Inactive</span>' !!}
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-0">

        <!-- 🔥 NAV TABS -->
        <div class="px-4 pb-3">

            <ul class="nav nav-pills mb-3" id="userTabs">

                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#addresses">
                        <i class="ri-map-pin-line me-1"></i>User Addresses
                    </button>
                </li>

                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#bookings">
                        <i class="ri-calendar-check-line me-1"></i>User Bookings
                    </button>
                </li>

                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#slots">
                        <i class="ri-time-line me-1"></i>Booking Slots
                    </button>
                </li>

                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#devices">
                        <i class="ri-smartphone-line me-1"></i>User Devices
                    </button>
                </li>

            </ul>

            <div class="tab-content">

                <!-- ADDRESSES -->
                <div class="tab-pane fade show active" id="addresses">
                    <div id="addresses-container">
                        @include('admin.users.partials.addresses_tab')
                    </div>
                </div>

                <!-- BOOKINGS -->
                <div class="tab-pane fade" id="bookings">
                    <div id="bookings-container">
                        @include('admin.users.partials.bookings_tab')
                    </div>
                </div>

                <!-- Booking Slots -->
                <div class="tab-pane fade" id="slots">
                    <div id="slots-container">
                        @include('admin.users.partials.slots_tab')
                    </div>
                </div>

                <!-- DEVICES -->
                <div class="tab-pane fade" id="devices">
                    <div id="devices-container">
                        @include('admin.users.partials.devices_tab')
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

            // 2. When a tab is clicked, keep the URL in sync (no reload) and reset to page 1
            document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function(tabEl) {
                tabEl.addEventListener('shown.bs.tab', function(e) {
                    const tabId = e.target.getAttribute('data-bs-target').replace('#', '');
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', tabId);

                    // Remove all pagination params from URL so a fresh reload starts on page 1
                    const keysToDelete = [];
                    url.searchParams.forEach((val, key) => {
                        if (key.endsWith('_page')) keysToDelete.push(key);
                    });
                    keysToDelete.forEach(k => url.searchParams.delete(k));

                    history.replaceState(null, '', url.toString());

                    // Reset the DOM to page 1 if it's currently on another page
                    const containerId = `${tabId}-container`;
                    const container = document.getElementById(containerId);
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
                                .then(response => response.text())
                                .then(html => {
                                    container.innerHTML = html;
                                    container.style.opacity = '1';
                                })
                                .catch(error => {
                                    console.error('Error fetching page 1:', error);
                                    container.style.opacity = '1';
                                });
                        }
                    }
                });
            });

            // 3. Intercept clicks inside each tab pane:
            //    - Pagination links (contain a _page= param) → fetch new page
            //    - Same-page reset/filter links (same pathname) → fetch filtered partial
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

                    // Only intercept pagination links or same-page links (e.g. reset filter)
                    if (!isPagination && !isSamePage) return;

                    e.preventDefault();

                    linkUrl.searchParams.set('ajax_tab', tabId);
                    container.style.opacity = '0.5';

                    fetch(linkUrl.toString(), {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => response.text())
                        .then(html => {
                            container.innerHTML = html;
                            container.style.opacity = '1';

                            const newUrl = new URL(linkUrl.toString());
                            newUrl.searchParams.delete('ajax_tab');
                            newUrl.searchParams.set('tab', tabId);
                            history.replaceState(null, '', newUrl.toString());
                        })
                        .catch(error => {
                            console.error('Error fetching data:', error);
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

                    // Append form field values to the URL
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
                        .then(response => response.text())
                        .then(html => {
                            container.innerHTML = html;
                            container.style.opacity = '1';

                            // Update browser URL to reflect active filters without reloading
                            const newUrl = new URL(fetchUrl.toString());
                            newUrl.searchParams.delete('ajax_tab');
                            newUrl.searchParams.set('tab', tabId);
                            history.replaceState(null, '', newUrl.toString());
                        })
                        .catch(error => {
                            console.error('Error applying filter:', error);
                            container.style.opacity = '1';
                        });
                });
            });

        });
    </script>
@endpush
