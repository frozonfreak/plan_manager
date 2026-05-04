<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Http\Controllers\Admin;

use FrozonFreak\PlanManager\Enums\PlanStatus;
use FrozonFreak\PlanManager\Models\Plan;
use FrozonFreak\PlanManager\Support\EntitlementCache;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PlanController
{
    public function index(): View
    {
        return view('plan-manager::admin.plans.index', [
            'plans' => Plan::query()->withCount('versions')->orderBy('sort_order')->orderBy('name')->paginate(20),
        ]);
    }

    public function show(Plan $plan): View
    {
        return view('plan-manager::admin.plans.show', [
            'plan' => $plan->load(['versions.featureValues.feature', 'versions.addons']),
        ]);
    }

    public function edit(Plan $plan): View
    {
        return view('plan-manager::admin.plans.edit', [
            'plan' => $plan,
            'statuses' => PlanStatus::cases(),
        ]);
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,active,archived'],
            'sort_order' => ['required', 'integer'],
        ]);

        $plan->update($data);
        EntitlementCache::bumpVersion();

        return redirect()->route(config('plan-manager.admin.route_name_prefix', 'plan-manager.').'plans.show', $plan)
            ->with('status', 'Plan updated.');
    }
}
