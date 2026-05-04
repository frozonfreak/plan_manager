<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Rules;

use FrozonFreak\PlanManager\Contracts\RuleActionApplier;
use FrozonFreak\PlanManager\Data\RuleEvaluationContext;

final class ActionApplier implements RuleActionApplier
{
    public function apply(array $actions, RuleEvaluationContext $context, array $state): array
    {
        foreach ($actions as $action) {
            $type = $action['type'] ?? null;

            match ($type) {
                'set_feature_value' => $state['features'][$action['feature']] = $action['value'] ?? null,
                'enable_addon' => $state['addons'][$action['addon']] = true,
                'disable_addon' => $state['addons'][$action['addon']] = false,
                'apply_display_discount' => $state['display_pricing']['discounts'][] = [
                    'label' => $action['label'] ?? 'Discount',
                    'type' => $action['value_type'] ?? 'fixed',
                    'value' => $action['value'] ?? null,
                ],
                'block_downgrade' => $state['blocks'][] = $action['reason'] ?? 'Downgrade is blocked.',
                'start_trial' => $state['trial_actions'][] = $action,
                'extend_trial' => $state['trial_actions'][] = $action,
                'end_trial' => $state['trial_actions'][] = $action,
                'convert_trial' => $state['trial_actions'][] = $action,
                default => $state['warnings'][] = "Unknown rule action [{$type}].",
            };
        }

        return $state;
    }
}
