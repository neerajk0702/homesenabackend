<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

    <!-- LOGO -->
    <div class="app-brand demo">
        <a href="{{ route('admin.dashboard') }}" class="app-brand-link d-flex align-items-center">
            <img src="{{ asset('assets/img/logo.svg') }}" alt="HomeSena Logo" style="height: 40px;">
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <li class="menu-item #">

        </li>
        {{-- Dashboard --}}
        <li class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <a href="{{ route('admin.dashboard') }}" class="menu-link">
                <i class="menu-icon ri ri-home-smile-line"></i>
                <div>Dashboard</div>
            </a>
        </li>

        {{-- Users --}}
        <li class="menu-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <a href="{{ route('admin.users.index') }}" class="menu-link">
                <i class="menu-icon ri ri-user-line"></i>
                <div>Users</div>
            </a>
        </li>

        {{-- Experts --}}
        {{-- <li class="menu-item {{ request()->routeIs('admin.experts.*') ? 'active' : '' }}">
            <a href="{{ route('admin.experts.index') }}" class="menu-link">
                <i class="menu-icon ri ri-user-star-line"></i>
                <div>Experts</div>
            </a>
        </li> --}}
        <li class="menu-item {{ request()->routeIs('admin.experts.*') ? 'active' : '' }}">
            <a href="{{ route('admin.experts.index') }}"
                class="menu-link d-flex justify-content-between align-items-center">

                <div>
                    <i class="menu-icon ri ri-user-star-line"></i>
                    Experts
                </div>

                @if (isset($pendingExpertsCount) && $pendingExpertsCount > 0)
                    <span class="badge bg-danger rounded-pill">
                        {{ $pendingExpertsCount }}
                    </span>
                @endif

            </a>
        </li>

        {{-- Time Slots --}}
        <li class="menu-item {{ request()->routeIs('admin.time_slots.*') ? 'active' : '' }}">
            <a href="{{ route('admin.time_slots.index') }}" class="menu-link">
                <i class="menu-icon ri ri-time-line"></i>
                <div>Time Slots</div>
            </a>
        </li>
        {{-- Notification --}}
        {{-- <li class="menu-item {{ request()->routeIs('admin.push_notifications.*') ? 'active' : '' }}">
            <a href="{{ route('admin.push_notifications.index') }}" class="menu-link">
                <i class="menu-icon ri-notification-3-line"></i>
                <div>Push Notifications</div>
            </a>
        </li> --}}
        {{-- Payments --}}
        {{-- <li class="menu-item {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
            <a href="{{ route('admin.payments.index') }}" class="menu-link">
                <i class="menu-icon ri ri-wallet-3-line"></i>
                <div>Payments</div>
            </a>
        </li> --}}
        {{-- Marketing Management --}}
        <li
            class="menu-item 
            {{ request()->routeIs('admin.push_notifications.*') ||
            request()->routeIs('admin.home_promotion.*') ||
            request()->routeIs('admin.coupons.*')
                ? 'active open'
                : '' }}">

            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon ri ri-megaphone-line"></i>

                <div>Marketing Management</div>
            </a>

            <ul class="menu-sub">

                <!-- Push Notifications -->
                <li class="menu-item {{ request()->routeIs('admin.push_notifications.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.push_notifications.index') }}" class="menu-link">

                        <div>Push Notifications</div>
                    </a>
                </li>

                <!-- Home Promotions -->
                <li class="menu-item {{ request()->routeIs('admin.home_promotion.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.home_promotion.index') }}" class="menu-link">

                        <div>Home Promotions</div>
                    </a>
                </li>

                <!-- Coupons -->
                <li class="menu-item {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.coupons.index') }}" class="menu-link">

                        <div>Coupons</div>
                    </a>
                </li>

            </ul>
        </li>
        {{-- Service Management --}}
        <li
            class="menu-item 
            {{ request()->routeIs('admin.services.*') || request()->routeIs('admin.service_variants.*') ? 'active open' : '' }}">

            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon ri ri-customer-service-2-line"></i>
                <div>Service Management</div>
            </a>

            <ul class="menu-sub">

                <li class="menu-item {{ request()->routeIs('admin.services.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.services.index') }}" class="menu-link">
                        <div>Services</div>
                    </a>
                </li>

                <li class="menu-item {{ request()->routeIs('admin.service_variants.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.service_variants.index') }}" class="menu-link">
                        <div>Service Durations</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.service_locations.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.service_locations.index') }}" class="menu-link">
                        <div>Service Location</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.service_notify.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.service_notify.index') }}" class="menu-link">
                        <div>Service Notify</div>
                    </a>
                </li>

            </ul>
        </li>
        {{-- Booking Management --}}
        <li
            class="menu-item 
            {{ request()->routeIs('admin.bookings.*') || request()->routeIs('admin.instant_bookings.*') ? 'active open' : '' }}">

            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon ri ri-calendar-check-line"></i>
                <div>Booking Management</div>
            </a>

            <ul class="menu-sub">

                {{-- Normal Bookings --}}
                <li class="menu-item {{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.bookings.index') }}" class="menu-link">
                        <div>Bookings</div>
                    </a>
                </li>

                {{-- Instant Bookings --}}
                <li class="menu-item {{ request()->routeIs('admin.instant_bookings.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.instant_bookings.index') }}" class="menu-link">
                        <div>Instant Bookings</div>
                    </a>
                </li>
                {{-- Booking Cancel Reasons --}}
                <li class="menu-item {{ request()->routeIs('admin.booking_cancel_reasons.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.booking_cancel_reasons.index') }}" class="menu-link">
                        <div>Booking Cancel Reasons</div>
                    </a>
                </li>
                {{-- Booking Reject Reasons --}}
                <li class="menu-item {{ request()->routeIs('admin.booking_reject_reasons.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.booking_reject_reasons.index') }}" class="menu-link">
                        <div>Booking Reject Reasons</div>
                    </a>
                </li>
            </ul>
        </li>
        {{-- Reviews --}}

        <li class="menu-item 
            {{ request()->routeIs('admin.reviews.*') ? 'active open' : '' }}">

            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon ri ri-star-line"></i>
                <div>Review Management</div>
            </a>

            <ul class="menu-sub">

                {{-- User Reviews --}}
                <li class="menu-item {{ request()->routeIs('admin.reviews.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.reviews.index') }}" class="menu-link">
                        <div>User Reviews</div>
                    </a>
                </li>

                {{-- Expert Reviews --}}
                <li class="menu-item {{ request()->routeIs('admin.reviews.expert_index') ? 'active' : '' }}">
                    <a href="{{ route('admin.reviews.expert_index') }}" class="menu-link">
                        <div>Experts Reviews</div>
                    </a>
                </li>

            </ul>
        </li>
        {{-- Refer & Earn --}}
        <li class="menu-item 
            {{ request()->routeIs('admin.refer_earn.*') ? 'active open' : '' }}">

            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon ri ri-gift-line"></i>
                <div>Refer & Earn</div>
            </a>

            <ul class="menu-sub">

                <li class="menu-item {{ request()->routeIs('admin.refer_earn.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.refer_earn.index') }}" class="menu-link">
                        <div>Refer & Earn List</div>
                    </a>
                </li>

                <li class="menu-item {{ request()->routeIs('admin.refer_earn_settings.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.refer_earn_settings.index') }}" class="menu-link">
                        <div>Refer & Earn Settings</div>
                    </a>
                </li>

            </ul>
        </li>
        {{-- Support --}}
        <li
            class="menu-item 
             {{ request()->routeIs('admin.user_supports.*') || request()->routeIs('admin.expert_supports.*') ? 'active open' : '' }}">

            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon ri ri-customer-service-line"></i>
                <div>Support</div>
            </a>

            <ul class="menu-sub">

                <li class="menu-item {{ request()->routeIs('admin.user_supports.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.user_supports.index') }}" class="menu-link">
                        <div>User Support</div>
                    </a>
                </li>

                <li class="menu-item {{ request()->routeIs('admin.expert_supports.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.expert_supports.index') }}" class="menu-link">
                        <div>Expert Support</div>
                    </a>
                </li>

            </ul>
        </li>
        {{-- Training Centers --}}
        <li class="menu-item {{ request()->routeIs('admin.training_centers.*') ? 'active' : '' }}">
            <a href="{{ route('admin.training_centers.index') }}" class="menu-link">
                <i class="menu-icon ri ri-building-line"></i>
                <div>Training Centers</div>
            </a>
        </li>

        {{-- CMS Pages --}}
        <li class="menu-item {{ request()->routeIs('admin.cms_pages.*') ? 'active' : '' }}">
            <a href="{{ route('admin.cms_pages.index') }}" class="menu-link">
                <i class="menu-icon ri ri-file-text-line"></i>
                <div>CMS Pages</div>
            </a>
        </li>


        {{-- Home Promotion --}}
        {{-- <li class="menu-item {{ request()->routeIs('admin.home_promotion.*') ? 'active' : '' }}">
            <a href="{{ route('admin.home_promotion.index') }}" class="menu-link">
                <i class="menu-icon ri ri-megaphone-line"></i>
                <div>Home Promotion</div>
            </a>
        </li> --}}



        {{-- Expert SOS --}}
        {{-- <li class="menu-item {{ request()->routeIs('admin.expert_sos.*') ? 'active' : '' }}">
            <a href="{{ route('admin.expert_sos.index') }}" class="menu-link">
                <i class="menu-icon ri ri-alarm-warning-line"></i>
                <div>Expert SOS</div>
            </a>
        </li> --}}
        <li class="menu-item {{ request()->routeIs('admin.expert_sos.*') ? 'active' : '' }}">
            <a href="{{ route('admin.expert_sos.index') }}"
                class="menu-link d-flex justify-content-between align-items-center">

                <div>
                    <i class="menu-icon ri ri-alarm-warning-line"></i>
                    Expert SOS
                </div>

                @if ($pendingSosCount > 0)
                    <span class="badge bg-danger rounded-pill">
                        {{ $pendingSosCount }}
                    </span>
                @endif

            </a>
        </li>

    </ul>
</aside>
<style>
    .menu-item.active>.menu-link {
        background-color: #696cff !important;
        color: #fff !important;
        border-radius: 8px;
    }

    .menu-item.active>.menu-link i {
        color: #fff !important;
    }

    .menu-item.open>.menu-link {
        background-color: rgba(105, 108, 255, 0.1);
        color: #696cff;
    }

    .menu-link:hover {
        background-color: rgba(105, 108, 255, 0.08);
        border-radius: 8px;
        transition: 0.3s;
    }

    #layout-menu {
        height: 110vh;

    }
</style>
