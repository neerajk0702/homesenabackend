@php
    $detail = $expert->expertDetail;
    $approvalFilter = request('approval');
    $onlineFilter = request('is_online');

    // Apply filters: if a filter is set and the detail doesn't match, hide it
$visible = $detail !== null;
if ($visible && $approvalFilter !== null && $approvalFilter !== '') {
    $visible = $detail->approval_status === $approvalFilter;
}
if ($visible && $onlineFilter !== null && $onlineFilter !== '') {
        $visible = (string) $detail->is_online === $onlineFilter;
    }
@endphp

<div class="d-flex justify-content-end mb-3">
    <form method="GET" action="{{ route('admin.experts.show', $expert->id) }}"
        class="d-flex align-items-center gap-2 flex-wrap">
        <input type="hidden" name="tab" value="details">

        <!-- Approval -->
        <select name="approval" class="form-select form-select-sm" style="width:160px;">
            <option value="">Approval</option>
            <option value="approved" {{ request('approval') == 'approved' ? 'selected' : '' }}>Approved</option>
            <option value="pending" {{ request('approval') == 'pending' ? 'selected' : '' }}>Pending</option>
        </select>

        <!-- Online -->
        <select name="is_online" class="form-select form-select-sm" style="width:160px;">
            <option value="">Online</option>
            <option value="1" {{ request('is_online') === '1' ? 'selected' : '' }}>Yes</option>
            <option value="0" {{ request('is_online') === '0' ? 'selected' : '' }}>No</option>
        </select>

        <!-- Search Button -->
        <button class="btn btn-primary btn-sm">
            <i class="ri-search-line"></i>
        </button>

        <!-- Reset -->
        <a href="{{ route('admin.experts.show', ['expert' => $expert->id, 'tab' => 'details']) }}"
            class="btn btn-outline-secondary btn-sm">
            <i class="ri-refresh-line"></i>
        </a>
    </form>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-sm">
        <thead class="bg-label-secondary">
            <tr>
                <th>#</th>
                <th>Reg Code</th>
                <th>Approval</th>
                <th>Training Center</th>
                <th>Service Location</th>
                <th>Online</th>
                <th>Updated</th>
            </tr>
        </thead>
        <tbody>
            @if ($visible)
                <tr>
                    <td>1</td>
                    <td>{{ $detail->registration_code ?? '-' }}</td>
                    <td>
                        @if ($detail->approval_status === 'approved')
                            <span class="badge bg-label-success rounded-pill">Approved</span>
                        @else
                            <span class="badge bg-label-warning rounded-pill">Pending</span>
                        @endif
                    </td>
                    <td>{{ $detail->trainingCenter->name ?? '-' }}</td>
                    <td>{{ $detail->servicelocation->address ?? 'N/A' }}</td>

                    <td>
                        @if ($detail->is_online == 1)
                            <span class="badge bg-success">Yes</span>
                        @else
                            <span class="badge bg-secondary">No</span>
                        @endif
                    </td>
                    <td>{{ $detail->updated_at }}</td>
                </tr>
            @else
                <tr>
                    <td colspan="6" class="text-center">No Details Found</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
