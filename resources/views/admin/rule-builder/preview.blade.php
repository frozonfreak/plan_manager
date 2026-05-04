@extends('plan-manager::admin.layout')

@section('title', 'Generated Rule JSON')
@section('topbar', 'Rule Builder')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Generated Rule JSON</h1>
            <p class="page-subtitle">Copy these payloads into the rule form. Review the values before activating the rule.</p>
        </div>
        <div class="actions">
            <a class="button button-secondary" href="{{ route(config('plan-manager.admin.route_name_prefix', 'plan-manager.') . 'rule-builder.index') }}">Build Another</a>
        </div>
    </div>

    <div class="grid">
        <section class="panel">
            <h2 class="section-title">Conditions</h2>
            <textarea readonly spellcheck="false">{{ json_encode($conditions, JSON_PRETTY_PRINT) }}</textarea>
        </section>
        <section class="panel">
            <h2 class="section-title">Actions</h2>
            <textarea readonly spellcheck="false">{{ json_encode($actions, JSON_PRETTY_PRINT) }}</textarea>
        </section>
    </div>
@endsection
