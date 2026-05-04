@extends('plan-manager::admin.layout')

@section('title', 'Edit ' . $plan->code)
@section('topbar', 'Edit Plan')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Edit {{ $plan->code }}</h1>
            <p class="page-subtitle">Update display details and lifecycle status. Entitlement terms live on plan versions.</p>
        </div>
    </div>

    <form method="post" action="{{ route(config('plan-manager.admin.route_name_prefix', 'plan-manager.') . 'plans.update', $plan) }}" class="panel">
        @csrf
        @method('PUT')
        <div class="form-grid">
            <label>Name <input name="name" value="{{ old('name', $plan->name) }}"></label>
            <label>Status <select name="status">@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(old('status', $plan->status->value) === $status->value)>{{ $status->value }}</option>@endforeach</select></label>
            <label>Sort Order <input type="number" name="sort_order" value="{{ old('sort_order', $plan->sort_order) }}"></label>
            <label class="field-full">Description <textarea name="description">{{ old('description', $plan->description) }}</textarea></label>
        </div>
        <div class="form-actions">
            <button>Save Plan</button>
            <a class="button button-secondary" href="{{ route(config('plan-manager.admin.route_name_prefix', 'plan-manager.') . 'plans.show', $plan) }}">Cancel</a>
        </div>
    </form>
@endsection
