<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Rules;

use FrozonFreak\PlanManager\Contracts\RuleConditionEvaluator;
use FrozonFreak\PlanManager\Data\RuleEvaluationContext;
use FrozonFreak\PlanManager\Models\SubscriptionAssignment;
use FrozonFreak\PlanManager\Rules\Operators\EqualsOperator;
use FrozonFreak\PlanManager\Rules\Operators\GreaterThanOperator;
use FrozonFreak\PlanManager\Rules\Operators\GreaterThanOrEqualOperator;
use FrozonFreak\PlanManager\Rules\Operators\InOperator;
use FrozonFreak\PlanManager\Rules\Operators\LessThanOperator;
use FrozonFreak\PlanManager\Rules\Operators\LessThanOrEqualOperator;
use FrozonFreak\PlanManager\Rules\Operators\NotEqualsOperator;
use FrozonFreak\PlanManager\Rules\Operators\NotInOperator;
use Illuminate\Support\Arr;

final class ConditionEvaluator implements RuleConditionEvaluator
{
    public function passes(?array $conditions, RuleEvaluationContext $context): bool
    {
        if ($conditions === null || $conditions === []) {
            return true;
        }

        if (isset($conditions['all'])) {
            foreach ((array) $conditions['all'] as $condition) {
                if (! $this->passes($condition, $context)) {
                    return false;
                }
            }

            return true;
        }

        if (isset($conditions['any'])) {
            foreach ((array) $conditions['any'] as $condition) {
                if ($this->passes($condition, $context)) {
                    return true;
                }
            }

            return false;
        }

        return $this->evaluateLeaf($conditions, $context);
    }

    private function evaluateLeaf(array $condition, RuleEvaluationContext $context): bool
    {
        if (! isset($condition['field'], $condition['operator'])) {
            return false;
        }

        $actual = $this->fieldValue((string) $condition['field'], $context);
        if ($actual === '__unknown__') {
            logger()->debug('plan-manager unknown rule field', ['field' => $condition['field']]);

            return false;
        }

        $operator = match ((string) $condition['operator']) {
            '=' => new EqualsOperator,
            '!=' => new NotEqualsOperator,
            '>' => new GreaterThanOperator,
            '>=' => new GreaterThanOrEqualOperator,
            '<' => new LessThanOperator,
            '<=' => new LessThanOrEqualOperator,
            'in' => new InOperator,
            'not_in' => new NotInOperator,
            default => null,
        };

        return $operator ? $operator($actual, $condition['value'] ?? null) : false;
    }

    private function fieldValue(string $field, RuleEvaluationContext $context): mixed
    {
        if (str_starts_with($field, 'usage.')) {
            $parts = explode('.', $field);

            return Arr::get($context->usage, "{$parts[1]}.{$parts[2]}") ?? '__unknown__';
        }

        if (str_starts_with($field, 'trial.usage.')) {
            $parts = explode('.', $field);

            return $parts[3] === 'remaining'
                ? ($context->trial->remainingUsage($parts[2]) ?? '__unknown__')
                : '__unknown__';
        }

        return match ($field) {
            'plan.code' => $context->plan?->code ?? '__unknown__',
            'plan.version' => $context->planVersion?->version ?? '__unknown__',
            'subscription.status' => $context->assignment?->status?->value ?? null,
            'subscription.billing_cycle' => $context->assignment?->billing_cycle ?? $context->planVersion?->billing_cycle,
            'subject.id' => $context->subject?->getKey() ?? '__unknown__',
            'subject.type' => $context->subject?->getMorphClass() ?? '__unknown__',
            'subject.has_used_trial' => $this->hasUsedTrial($context),
            'subject.has_active_subscription' => $context->assignment?->status?->value === 'active',
            'subject.previous_trial_count' => $this->previousTrialCount($context),
            'account.country' => $context->account['country'] ?? '__unknown__',
            'account.segment' => $context->account['segment'] ?? '__unknown__',
            'trial.is_trialing' => $context->trial->isTrialing,
            'trial.type' => $context->trial->trialType,
            'trial.expired' => $context->trial->expired,
            'trial.remaining_days' => $context->trial->remainingDays(),
            'addon.code' => $context->addonCode ?? '__unknown__',
            default => '__unknown__',
        };
    }

    private function hasUsedTrial(RuleEvaluationContext $context): bool
    {
        return $this->previousTrialCount($context) > 0;
    }

    private function previousTrialCount(RuleEvaluationContext $context): int
    {
        if (! $context->subject) {
            return 0;
        }

        return SubscriptionAssignment::query()
            ->where('subject_type', $context->subject->getMorphClass())
            ->where('subject_id', (string) $context->subject->getKey())
            ->whereNotNull('trial_type')
            ->count();
    }
}
