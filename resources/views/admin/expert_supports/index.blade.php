@extends('admin.layouts.master')
@section('title', 'Expert Support')

@section('content')

    <div class="card">

        <!-- ALERT -->
        <div class="p-3">
            @include('admin.layouts.partials.alerts')
        </div>

        <!-- HEADER -->
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">

            <h5 class="card-title mb-0">Expert Support</h5>

            <!-- FILTER FORM -->
            <form method="GET" action="{{ route('admin.expert_supports.index') }}"
                class="d-flex align-items-center gap-2 flex-wrap">

                <!-- Search -->
                <input name="search" type="search" class="form-control form-control-sm" placeholder="Search expert..."
                    value="{{ request('search') }}" style="width:180px;">

                <!-- Type Filter -->
                <select name="type" class="form-select form-select-sm" style="width:130px;">
                    <option value="">Type</option>
                    <option value="chat" {{ request('type') == 'chat' ? 'selected' : '' }}>Chat</option>
                    <option value="email" {{ request('type') == 'email' ? 'selected' : '' }}>Email</option>
                    <option value="phone" {{ request('type') == 'phone' ? 'selected' : '' }}>Call</option>
                </select>

                <!-- Buttons -->
                <button class="btn btn-primary btn-sm">
                    <i class="ri-search-line"></i>
                </button>

                <a href="{{ route('admin.expert_supports.index') }}" class="btn btn-outline-secondary btn-sm">
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
                        <th width="60">ID</th>
                        <th>Expert</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Created At</th>
                        <th width="100">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($supports as $key => $support)
                        <tr>

                            <td>{{ $supports->firstItem() + $key }}</td>

                            <!-- Expert -->
                            <td>
                                <div class="fw-semibold">
                                    {{ $support->expert->name ?? 'N/A' }}
                                </div>
                            </td>

                            <!-- Type -->
                            <td class="fw-bold">
                                @if ($support->type == 'chat')
                                    <i class="ri-chat-3-line me-1"></i> Chat
                                @elseif($support->type == 'email')
                                    <i class="ri-mail-line me-1"></i> Email
                                @elseif($support->type == 'phone')
                                    <i class="ri-phone-line me-1"></i> Call
                                @endif
                            </td>

                            <!-- Value -->
                            <td>{{ Str::words($support->value, 10, '...') }}</td>

                            <!-- Date -->
                            <td>
                                {{ $support->created_at->format('d M Y, h:i A') }}
                            </td>

                            <!-- ACTION -->
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill"
                                        data-bs-toggle="dropdown">
                                        <i class="ri-more-2-line"></i>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end">
                                        {{-- Details --}}
                                        <li>
                                            <a class="dropdown-item"
                                                href="{{ route('admin.expert_supports.show', $support->id) }}">
                                                <i class="ri-eye-line me-2"></i> Details
                                            </a>
                                        </li>
                                        <!-- Delete -->
                                        <li>
                                            <form action="{{ route('admin.expert_supports.destroy', $support->id) }}"
                                                method="POST" onsubmit="return confirm('Delete this record?')">
                                                @csrf
                                                @method('DELETE')

                                                <button class="dropdown-item text-danger">
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
                            <td colspan="6" class="text-center">No Data Found</td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        <!-- PAGINATION -->
        <div class="row px-4 pb-3">
            {{ $supports->links('pagination::bootstrap-5') }}
        </div>

    </div>

@endsection
