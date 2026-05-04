<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Http\Controllers\Admin;

use FrozonFreak\PlanManager\Enums\RuleStatus;
use FrozonFreak\PlanManager\Enums\RuleType;
use FrozonFreak\PlanManager\Enums\StackingPolicy;
use FrozonFreak\PlanManager\Models\PlanRule;
use FrozonFreak\PlanManager\Support\EntitlementCache;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RuleController
{
    public function index(): View
    {
        return view('plan-manager::admin.rules.index', [
            'rules' => PlanRule::query()->orderBy('priority')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return $this->form(new PlanRule);
    }

    public function store(Request $request): RedirectResponse
    {
        PlanRule::query()->create($this->validated($request));
        EntitlementCache::bumpVersion();

        return redirect()->route(config('plan-manager.admin.route_name_prefix', 'plan-manager.').'rules.index')
            ->with('status', 'Rule created.');
    }

    public function edit(PlanRule $rule): View
    {
        return $this->form($rule);
    }

    public function update(Request $request, PlanRule $rule): RedirectResponse
    {
        $rule->update($this->validated($request));
        EntitlementCache::bumpVersion();

        return redirect()->route(config('plan-manager.admin.route_name_prefix', 'plan-manager.').'rules.index')
            ->with('status', 'Rule updated.');
    }

    private function form(PlanRule $rule): View
    {
        return view('plan-manager::admin.rules.form', [
            'rule' => $rule,
            'types' => RuleType::cases(),
            'statuses' => RuleStatus::cases(),
            'stackingPolicies' => StackingPolicy::cases(),
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'rule_type' => ['required', 'string'],
            'conditions_json' => ['nullable', 'json'],
            'actions_json' => ['nullable', 'json'],
            'priority' => ['required', 'integer'],
            'stacking_policy' => ['required', 'string'],
            'status' => ['required', 'string'],
        ]);

        $data['conditions_json'] = $data['conditions_json'] ? json_decode($data['conditions_json'], true, flags: JSON_THROW_ON_ERROR) : null;
        $data['actions_json'] = $data['actions_json'] ? json_decode($data['actions_json'], true, flags: JSON_THROW_ON_ERROR) : null;

        return $data;
    }
}
