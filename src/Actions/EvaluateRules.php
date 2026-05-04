<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Actions;

use FrozonFreak\PlanManager\Contracts\RuleActionApplier;
use FrozonFreak\PlanManager\Contracts\RuleConditionEvaluator;
use FrozonFreak\PlanManager\Data\RuleEvaluationContext;
use FrozonFreak\PlanManager\Enums\StackingPolicy;
use FrozonFreak\PlanManager\Models\PlanRule;

final class EvaluateRules
{
    public function __construct(
        private readonly RuleConditionEvaluator $conditions,
        private readonly RuleActionApplier $actions,
    ) {}

    public function handle(RuleEvaluationContext $context, array $state = []): array
    {
        $state += [
            'features' => $context->features,
            'addons' => $context->addons,
            'usage' => $context->usage,
            'display_pricing' => ['discounts' => []],
            'warnings' => [],
            'blocks' => [],
            'trial_actions' => [],
            'applied_rules' => [],
        ];

        foreach (PlanRule::query()->active()->orderBy('priority')->get() as $rule) {
            if (! $this->conditions->passes($rule->conditions_json, $context)) {
                continue;
            }

            $state = $this->actions->apply((array) $rule->actions_json, $context, $state);
            $state['applied_rules'][] = [
                'id' => $rule->id,
                'code' => $rule->code,
                'name' => $rule->name,
                'type' => $rule->rule_type->value,
            ];

            if ($rule->stacking_policy === StackingPolicy::StopProcessing) {
                break;
            }
        }

        return $state;
    }
}
