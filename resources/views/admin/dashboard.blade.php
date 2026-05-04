@extends('plan-manager::admin.layout')

@section('title', 'Plan Manager Dashboard')
@section('topbar', 'Dashboard')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">A quick operational view of plan configuration, rule coverage, pending approvals, and usage correction activity.</p>
        </div>
    </div>

    <div class="grid">
        <div class="panel stat">
            <div class="stat-label">Plans</div>
            <div class="stat-value">{{ $plansCount }}</div>
            <div class="stat-note">Versioned product packages</div>
        </div>
        <div class="panel stat">
            <div class="stat-label">Rules</div>
            <div class="stat-value">{{ $rulesCount }}</div>
            <div class="stat-note">Eligibility and entitlement modifiers</div>
        </div>
        <div class="panel stat">
            <div class="stat-label">Pending Approvals</div>
            <div class="stat-value">{{ $pendingApprovalsCount }}</div>
            <div class="stat-note">Plan changes waiting for review</div>
        </div>
        <div class="panel stat">
            <div class="stat-label">Corrections</div>
            <div class="stat-value">{{ $correctionsCount }}</div>
            <div class="stat-note">Append-only usage adjustments</div>
        </div>
    </div>
@endsection
