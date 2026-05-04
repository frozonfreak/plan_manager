<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class RuleBuilderController
{
    public function index(): View
    {
        return view('plan-manager::admin.rule-builder.index', [
            'fields' => [
                'plan.code', 'plan.version', 'subscription.status', 'subscription.billing_cycle',
                'subject.has_used_trial', 'subject.has_active_subscription', 'account.country',
                'account.segment', 'trial.is_trialing', 'trial.type', 'trial.expired',
            ],
            'operators' => ['=', '!=', '>', '>=', '<', '<=', 'in', 'not_in'],
            'actions' => ['set_feature_value', 'enable_addon', 'disable_addon', 'apply_display_discount', 'block_downgrade', 'start_trial', 'extend_trial', 'end_trial', 'convert_trial'],
        ]);
    }

    public function preview(Request $request): View
    {
        $data = $request->validate([
            'field' => ['required', 'string'],
            'operator' => ['required', 'string'],
            'value' => ['nullable', 'string'],
            'action_type' => ['required', 'string'],
            'target' => ['nullable', 'string'],
            'action_value' => ['nullable', 'string'],
        ]);

        $conditionValue = str_contains((string) $data['value'], ',')
            ? array_map('trim', explode(',', (string) $data['value']))
            : $this->scalar($data['value'] ?? null);

        $action = ['type' => $data['action_type']];
        if (($data['target'] ?? null) !== null) {
            $key = in_array($data['action_type'], ['enable_addon', 'disable_addon'], true) ? 'addon' : 'feature';
            $action[$key] = $data['target'];
        }
        if (($data['action_value'] ?? null) !== null) {
            $action['value'] = $this->scalar($data['action_value']);
        }

        return view('plan-manager::admin.rule-builder.preview', [
            'conditions' => ['all' => [[
                'field' => $data['field'],
                'operator' => $data['operator'],
                'value' => $conditionValue,
            ]]],
            'actions' => [$action],
        ]);
    }

    private function scalar(?string $value): mixed
    {
        return match (true) {
            $value === null => null,
            $value === 'true' => true,
            $value === 'false' => false,
            is_numeric($value) => $value + 0,
            default => $value,
        };
    }
}
