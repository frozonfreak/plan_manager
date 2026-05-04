@extends('plan-manager::admin.layout')

@section('title', 'Plan Change Approvals')
@section('topbar', 'Approvals')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Plan Change Approvals</h1>
            <p class="page-subtitle">Review requested plan moves before applying them to local subscription assignments.</p>
        </div>
    </div>

    <div class="table-shell">
        <div class="table-scroll">
            <table>
                <thead><tr><th>Subject</th><th>Target</th><th>Status</th><th>Reason</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($requests as $request)
                    <tr>
                        <td>
                            <code>{{ $request->subject_type }}</code>
                            <div class="muted">ID {{ $request->subject_id }}</div>
                        </td>
                        <td>
                            <strong>{{ $request->targetPlan->code }}</strong>
                            <div class="muted">Version {{ $request->targetPlanVersion->version }}</div>
                        </td>
                        <td>
                            <span class="badge @if($request->status->value === 'approved') badge-success @elseif($request->status->value === 'rejected') badge-danger @elseif($request->status->value === 'pending') badge-warning @endif">{{ $request->status->value }}</span>
                        </td>
                        <td>{{ $request->reason ?: 'No reason provided' }}</td>
                        <td>
                            <div class="actions">
                                <form method="post" action="{{ route(config('plan-manager.admin.route_name_prefix', 'plan-manager.') . 'approvals.approve', $request) }}">@csrf<button>Approve</button></form>
                                <form method="post" action="{{ route(config('plan-manager.admin.route_name_prefix', 'plan-manager.') . 'approvals.reject', $request) }}">@csrf<button class="button-danger">Reject</button></form>
                                <form method="post" action="{{ route(config('plan-manager.admin.route_name_prefix', 'plan-manager.') . 'approvals.apply', $request) }}">@csrf<button class="button-secondary">Apply</button></form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty-state">No plan change requests are waiting here.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="pagination">{{ $requests->links() }}</div>
@endsection
