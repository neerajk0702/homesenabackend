<!-- Layout wrapper -->

<!-- Menu -->

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="index.html" class="app-brand-link">
            <span class="app-brand-logo demo me-1">
                <span class="text-primary">
                </span>
            </span>
            <!-- <span class="app-brand-text demo menu-text fw-semibold ms-2"> -->
            <a href="#" class="app-brand-link d-flex align-items-center">
                <img src="{{ asset('assets/img/logo.svg') }}" alt="HomeSena Logo" style="height: 40px; width: auto;">
            </a>
            <!-- </span> -->
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <li class="menu-item">
        </li>
        <!-- Dashboards -->
        <li class="menu-item">
            <a href="{{ route('admin.dashboard') }}" class="menu-link">
                <i class="menu-icon icon-base ri ri-home-smile-line"></i>
                <div data-i18n="Dashboards">Dashboards</div>
            </a>
        </li>
        {{-- User --}}
        <li class="menu-item">
            <a href="{{ route('admin.users.index') }}" class="menu-link">
                <i class="menu-icon icon-base ri ri-user-line"></i>
                <div data-i18n="User">Users</div>
            </a>
        </li>
        {{-- Experts --}}
        <li class="menu-item">
            <a href="{{ route('admin.experts.index') }}" class="menu-link">
                <i class="menu-icon icon-base ri ri-user-line"></i>
                <div data-i18n="Expert">Experts</div>
            </a>
        </li>
        {{-- Service Management--}}
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ri ri-customer-service-2-line"></i>
                <div data-i18n="Service Management">Service Management</div>
            </a>

            <ul class="menu-sub">
                {{-- Services --}}
                <li class="menu-item">
                    <a href="{{ route('admin.services.index') }}" class="menu-link">
                        <i class="menu-icon icon-base ri ri-customer-service-2-line"></i>
                        <div data-i18n="Services">Services</div>
                    </a>
                </li>

                {{-- Service Durations --}}
                <li class="menu-item">
                    <a href="{{ route('admin.service_variants.index') }}" class="menu-link">
                        <i class="menu-icon icon-base ri ri-timer-line"></i>
                        <div data-i18n="Service Durations">Service Durations</div>
                    </a>
                </li>
            </ul>
        </li>
        {{-- Services --}}
        <!-- <li class="menu-item">
            <a href="{{ route('admin.services.index') }}" class="menu-link">
                <i class="menu-icon icon-base ri ri-customer-service-2-line"></i>
                <div data-i18n="Service">Services</div>
            </a>
        </li> -->
        {{-- Time slots --}}
        <li class="menu-item">
            <a href="{{ route('admin.time_slots.index') }}" class="menu-link">
                <i class="menu-icon icon-base ri ri-time-line"></i>
                <div data-i18n="Time Slots">Time Slots</div>
            </a>
        </li>
        {{-- service durations --}}
        <!-- <li class="menu-item">
            <a href="{{ route('admin.service_variants.index') }}" class="menu-link">
                <i class="menu-icon icon-base ri ri-timer-line"></i>
                <div data-i18n="Service Duration">Service Durations</div>
            </a>
        </li> -->
        {{-- Bookings --}}
        <li class="menu-item">
            <a href="{{ route('admin.bookings.index') }}" class="menu-link">
                <i class="menu-icon icon-base ri ri-calendar-check-line"></i>
                <div data-i18n="Bookings">Bookings</div>
            </a>
        </li>
        {{-- Training Centers --}}
        <li class="menu-item">
            <a href="{{ route('admin.training_centers.index') }}" class="menu-link">
                <i class="menu-icon icon-base ri ri-building-line"></i>
                <div data-i18n="Training Centers">Training Centers</div>
            </a>
        </li>
        {{-- Cms Pages --}}
        <li class="menu-item">
            <a href="{{ route('admin.cms_pages.index') }}" class="menu-link">
                <i class="menu-icon icon-base ri ri-file-text-line"></i>
                <div data-i18n="CMS Pages">CMS Pages</div>
            </a>
        </li>
        {{-- Review --}}
        <li class="menu-item">
            <a href="{{ route('admin.reviews.index') }}" class="menu-link">
                <i class="menu-icon icon-base ri ri-star-line"></i>
                <div data-i18n="Reviews">Reviews</div>
            </a>
        </li>
        {{-- Home Promotion --}}
        <li class="menu-item">
            <a href="{{ route('admin.home_promotion.index') }}" class="menu-link">
                <i class="menu-icon icon-base ri ri-megaphone-line"></i>
                <div data-i18n="Home Promotion">Home Promotion</div>
            </a>
        </li>
        {{-- Support --}}
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ri ri-customer-service-2-line"></i>
                <div data-i18n="Support">Support</div>
            </a>

            <ul class="menu-sub">

                <!-- User Support -->
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="User Support">User Support</div>
                    </a>
                </li>

                <!-- Expert Support -->
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="Expert Support">Expert Support</div>
                    </a>
                </li>

            </ul>
        </li>
        {{-- Expert Setting --}}
        <li class="menu-item">
            <a href="#" class="menu-link">
                <i class="menu-icon icon-base ri ri-user-star-line"></i>
                <div data-i18n="Expert Settings">Expert Settings</div>
            </a>
        </li>
        {{-- Payments --}}
        <!-- <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon icon-base ri ri-secure-payment-line"></i>
                    <div data-i18n="Payments">Payments</div>
                </a> -->

        <!-- <ul class="menu-sub"> -->

        <!-- Payment -->
        <!-- <li class="menu-item">
                        <a href="{{ route('admin.payments.index') }}" class="menu-link">
                            <div data-i18n="Payment">Payment</div>
                        </a>
                    </li> -->

        <!-- Payment Method -->
        <!-- <li class="menu-item">
                        <a href="" class="menu-link">
                            <div data-i18n="Payment Method">Payment Method</div>
                        </a>
                    </li> -->

        <!-- </ul> -->
        <!-- </li> -->
        <!-- Layouts -->
    </ul>
</aside>
<!-- / Menu -->
