@extends('plan-manager::admin.layout')

@section('title', 'Plans')
@section('topbar', 'Plans')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Plans</h1>
            <p class="page-subtitle">Browse plan codes, lifecycle status, and version counts. Codes should remain stable for application checks.</p>
        </div>
    </div>

    <div class="table-shell">
        <div class="table-scroll">
            <table>
                <thead><tr><th>Code</th><th>Name</th><th>Status</th><th>Versions</th><th></th></tr></thead>
                <tbody>
                @forelse($plans as $plan)
                    <tr>
                        <td><code>{{ $plan->code }}</code></td>
                        <td><strong>{{ $plan->name }}</strong></td>
                        <td><span class="badge @if($plan->status->value === 'active') badge-success @elseif($plan->status->value === 'archived') badge-danger @endif">{{ $plan->status->value }}</span></td>
                        <td>{{ $plan->versions_count }}</td>
                        <td><a class="button button-secondary" href="{{ route(config('plan-manager.admin.route_name_prefix', 'plan-manager.') . 'plans.show', $plan) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty-state">No plans have been created yet.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="pagination">{{ $plans->links() }}</div>
@endsection
