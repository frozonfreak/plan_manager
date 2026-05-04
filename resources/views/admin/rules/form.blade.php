@extends('plan-manager::admin.layout')

@section('title', $rule->exists ? 'Edit Rule' : 'New Rule')
@section('topbar', $rule->exists ? 'Edit Rule' : 'New Rule')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $rule->exists ? 'Edit Rule' : 'New Rule' }}</h1>
            <p class="page-subtitle">Conditions decide when a rule applies. Actions mutate preview or entitlement state without touching billing.</p>
        </div>
        <div class="actions">
            <a class="button button-secondary" href="{{ route(config('plan-manager.admin.route_name_prefix', 'plan-manager.') . 'rule-builder.index') }}">Open Builder</a>
        </div>
    </div>

    <form method="post" action="{{ $rule->exists ? route(config('plan-manager.admin.route_name_prefix', 'plan-manager.') . 'rules.update', $rule) : route(config('plan-manager.admin.route_name_prefix', 'plan-manager.') . 'rules.store') }}" class="panel">
        @csrf
        @if($rule->exists) @method('PUT') @endif
        <div class="form-grid">
            <label>Name <input name="name" value="{{ old('name', $rule->name) }}"></label>
            <label>Code <input name="code" value="{{ old('code', $rule->code) }}" placeholder="business-yearly-discount"></label>
            <label>Type <select name="rule_type">@foreach($types as $type)<option value="{{ $type->value }}" @selected(old('rule_type', $rule->rule_type?->value) === $type->value)>{{ $type->value }}</option>@endforeach</select></label>
            <label>Priority <input type="number" name="priority" value="{{ old('priority', $rule->priority ?? 100) }}"></label>
            <label>Stacking <select name="stacking_policy">@foreach($stackingPolicies as $policy)<option value="{{ $policy->value }}" @selected(old('stacking_policy', $rule->stacking_policy?->value ?? 'can_stack') === $policy->value)>{{ $policy->value }}</option>@endforeach</select></label>
            <label>Status <select name="status">@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(old('status', $rule->status?->value ?? 'draft') === $status->value)>{{ $status->value }}</option>@endforeach</select></label>
            <label class="field-full">Conditions JSON <textarea name="conditions_json" spellcheck="false">{{ old('conditions_json', $rule->conditions_json ? json_encode($rule->conditions_json, JSON_PRETTY_PRINT) : '') }}</textarea></label>
            <label class="field-full">Actions JSON <textarea name="actions_json" spellcheck="false">{{ old('actions_json', $rule->actions_json ? json_encode($rule->actions_json, JSON_PRETTY_PRINT) : '') }}</textarea></label>
        </div>
        <div class="form-actions">
            <button>{{ $rule->exists ? 'Save Rule' : 'Create Rule' }}</button>
            <a class="button button-secondary" href="{{ route(config('plan-manager.admin.route_name_prefix', 'plan-manager.') . 'rules.index') }}">Cancel</a>
        </div>
    </form>
@endsection
