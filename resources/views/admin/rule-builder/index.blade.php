@extends('plan-manager::admin.layout')

@section('title', 'Rule Builder')
@section('topbar', 'Rule Builder')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Rule Builder</h1>
            <p class="page-subtitle">Generate a starter condition/action payload, then paste it into a rule and adjust as needed.</p>
        </div>
    </div>

    <form method="post" action="{{ route(config('plan-manager.admin.route_name_prefix', 'plan-manager.') . 'rule-builder.preview') }}" class="panel">
        @csrf
        <h2 class="section-title">Condition</h2>
        <div class="form-grid">
            <label>Field <select name="field">@foreach($fields as $field)<option>{{ $field }}</option>@endforeach</select></label>
            <label>Operator <select name="operator">@foreach($operators as $operator)<option>{{ $operator }}</option>@endforeach</select></label>
            <label class="field-full">Value <input name="value" placeholder="pro"></label>
        </div>

        <h2 class="section-title" style="margin-top: 20px;">Action</h2>
        <div class="form-grid">
            <label>Action <select name="action_type">@foreach($actions as $action)<option>{{ $action }}</option>@endforeach</select></label>
            <label>Target <input name="target" placeholder="users.max or advanced_reports"></label>
            <label class="field-full">Action Value <input name="action_value" placeholder="10"></label>
        </div>

        <div class="form-actions">
            <button>Generate JSON</button>
            <a class="button button-secondary" href="{{ route(config('plan-manager.admin.route_name_prefix', 'plan-manager.') . 'rules.index') }}">Back to Rules</a>
        </div>
    </form>
@endsection
