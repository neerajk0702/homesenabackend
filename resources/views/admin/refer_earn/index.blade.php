@extends('admin.layouts.master')
@section('title', 'Refer & Earn')

@section('content')
    <div class="card">
        <!-- ALERT MESSAGE -->
        <div class="p-3">
            @include('admin.layouts.partials.alerts')
        </div>
        <!-- Header -->
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Refer & Earn</h5>
            <div class="d-flex align-items-center gap-3">
                <!-- Search -->
                <form method="GET" action="{{ route('admin.users.index') }}" class="d-flex align-items-center">
                    <div class="d-flex align-items-center">
                        <span class="me-2">Search:</span>
                        <input name="search" type="search" class="form-control form-control-sm"
                            placeholder="Search Users..." value="{{ request('search') }}" style="width:200px;">
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
                        <th>User</th>
                        <th>Referral Code</th>
                        <th>Referred By</th>
                        <th>Total Referrals</th>
                        <th width="120">Reward</th>
                        <th>Created At</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <!-- Pagination Index -->
                            <td>
                                {{ $loop->iteration + ($users->currentPage() - 1) * $users->perPage() }}
                            </td>

                            <!-- User -->
                            <td>
                                <span class="fw-semibold">{{ $user->name }}</span>
                            </td>

                            <!-- Referral Code -->
                            <td>
                                <span class="badge bg-primary">
                                    {{ $user->referral_code ?? '-' }}
                                </span>
                            </td>

                            <!-- Referred By -->
                            <td>
                                {{ $user->referrer->name ?? 'N/A' }}
                            </td>

                            <!-- Total Referrals -->
                            <td>
                                <span class="badge bg-primary">
                                    {{ $user->referrals->count() }}
                                </span>
                            </td>

                            <!-- Reward Toggle -->
                            <td>
                                @if ($user->referral_reward_given)
                                    <span class="badge bg-success">Given</span>
                                @else
                                    <span class="badge bg-warning">Pending</span>
                                @endif
                            </td>

                            <!-- Created -->
                            <td>
                                {{ $user->updated_at->format('d M Y') }}
                            </td>

                            <!-- Actions -->
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill"
                                        data-bs-toggle="dropdown">
                                        <i class="ri-more-2-line"></i>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end">

                                        <!-- View -->
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.refer_earn.show', $user->id) }}">
                                                <i class="ri-eye-line me-2"></i> Details
                                            </a>
                                        </li>

                                    </ul>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">No data found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination (Dynamic) -->
        <div class="row px-4 pb-3 align-items-center">


            {{ $users->links('pagination::bootstrap-5') }}


        </div>

    @endsection
