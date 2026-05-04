@extends('plan-manager::admin.layout')

@section('title', 'Rules')
@section('topbar', 'Rules')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Rules</h1>
            <p class="page-subtitle">Rule execution is priority ordered. Keep rules narrow and auditable so entitlement changes remain explainable.</p>
        </div>
        <div class="actions">
            <a class="button" href="{{ route(config('plan-manager.admin.route_name_prefix', 'plan-manager.') . 'rules.create') }}">New Rule</a>
            <a class="button button-secondary" href="{{ route(config('plan-manager.admin.route_name_prefix', 'plan-manager.') . 'rule-builder.index') }}">Builder</a>
        </div>
    </div>

    <div class="table-shell">
        <div class="table-scroll">
            <table>
                <thead><tr><th>Priority</th><th>Name</th><th>Type</th><th>Status</th><th>Stacking</th><th></th></tr></thead>
                <tbody>
                @forelse($rules as $rule)
                    <tr>
                        <td><strong>{{ $rule->priority }}</strong></td>
                        <td>
                            <strong>{{ $rule->name }}</strong>
                            @if($rule->code)<div><code>{{ $rule->code }}</code></div>@endif
                        </td>
                        <td><span class="badge">{{ $rule->rule_type->value }}</span></td>
                        <td><span class="badge @if($rule->status->value === 'active') badge-success @elseif($rule->status->value === 'archived') badge-danger @endif">{{ $rule->status->value }}</span></td>
                        <td>{{ $rule->stacking_policy->value }}</td>
                        <td><a class="button button-secondary" href="{{ route(config('plan-manager.admin.route_name_prefix', 'plan-manager.') . 'rules.edit', $rule) }}">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state">No rules have been created yet.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="pagination">{{ $rules->links() }}</div>
@endsection
