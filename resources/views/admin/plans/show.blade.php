@extends('plan-manager::admin.layout')

@section('title', $plan->name)
@section('topbar', 'Plan Details')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $plan->name }}</h1>
            <p class="page-subtitle"><code>{{ $plan->code }}</code> / <span class="badge @if($plan->status->value === 'active') badge-success @elseif($plan->status->value === 'archived') badge-danger @endif">{{ $plan->status->value }}</span></p>
        </div>
        <div class="actions">
            <a class="button" href="{{ route(config('plan-manager.admin.route_name_prefix', 'plan-manager.') . 'plans.edit', $plan) }}">Edit Plan</a>
            <a class="button button-secondary" href="{{ route(config('plan-manager.admin.route_name_prefix', 'plan-manager.') . 'plans.index') }}">Back</a>
        </div>
    </div>

    @foreach($plan->versions as $version)
        <section class="panel">
            <div class="page-header" style="margin-bottom: 12px;">
                <div>
                    <h2 class="section-title">Version {{ $version->version }}</h2>
                    <p class="page-subtitle">
                        <span class="badge @if($version->status->value === 'active') badge-success @elseif($version->status->value === 'archived') badge-danger @endif">{{ $version->status->value }}</span>
                        <span class="muted">{{ $version->billing_cycle ?? 'custom' }} / {{ $version->currency }} {{ $version->display_price ?? '0.00' }}</span>
                    </p>
                </div>
            </div>
            <div class="table-shell">
                <div class="table-scroll">
                    <table>
                        <thead><tr><th>Feature</th><th>Value</th></tr></thead>
                        <tbody>
                        @forelse($version->featureValues as $value)
                            <tr>
                                <td><code>{{ $value->feature->code }}</code></td>
                                <td><code>{{ json_encode($value->value) }}</code></td>
                            </tr>
                        @empty
                            <tr><td colspan="2"><div class="empty-state">No feature values configured for this version.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endforeach
@endsection
