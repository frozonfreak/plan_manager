<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Actions;

use FrozonFreak\PlanManager\Data\PreviewResult;
use FrozonFreak\PlanManager\Data\RuleEvaluationContext;
use FrozonFreak\PlanManager\Data\TrialResult;
use FrozonFreak\PlanManager\Enums\TrialType;
use FrozonFreak\PlanManager\Exceptions\PlanNotFoundException;
use FrozonFreak\PlanManager\Exceptions\PlanVersionNotFoundException;
use FrozonFreak\PlanManager\Models\Plan;
use FrozonFreak\PlanManager\Models\SubscriptionAssignment;
use Illuminate\Database\Eloquent\Model;

final class PreviewPlanChange
{
    public function __construct(private readonly EvaluateRules $rules) {}

    public function handle(array $input): PreviewResult
    {
        $subject = $input['subject'] ?? null;
        $planCode = (string) $input['plan'];
        $plan = Plan::query()->where('code', $planCode)->first();
        if (! $plan) {
            throw new PlanNotFoundException("Plan [{$planCode}] was not found.");
        }

        $planVersion = $plan->activeVersion($input['version'] ?? null, $input['billing_cycle'] ?? null);
        if (! $planVersion) {
            throw new PlanVersionNotFoundException("Active version for plan [{$planCode}] was not found.");
        }

        $features = [];
        foreach ($planVersion->featureValues()->with('feature')->get() as $value) {
            $features[$value->feature->code] = $value->castedValue();
        }

        $addons = [];
        foreach ($planVersion->addons()->get() as $addon) {
            $addons[$addon->code] = (bool) $addon->pivot->is_available;
        }
        foreach ((array) ($input['addons'] ?? []) as $addonCode) {
            $addons[$addonCode] = true;
        }

        $usage = [];
        foreach ((array) ($input['usage'] ?? []) as $meter => $used) {
            $limit = $features[$meter.'.monthly'] ?? null;
            $limit = is_numeric($limit) ? (float) $limit : null;
            $remaining = $limit === null || $limit < 0 ? null : (float) $limit - (float) $used;
            $usage[$meter] = [
                'limit' => $limit,
                'used' => (float) $used,
                'remaining' => $remaining,
                'exceeded' => $limit !== null && $limit >= 0 && (float) $used > $limit,
            ];
        }

        $warnings = [];
        foreach ($usage as $meter => $row) {
            if ($row['exceeded']) {
                $warnings[] = "Usage exceeds included {$meter} limit";
            }
        }

        $trial = $this->trialPreview($subject instanceof Model ? $subject : null, (array) ($input['trial'] ?? []));

        $state = $this->rules->handle(new RuleEvaluationContext(
            subject: $subject instanceof Model ? $subject : null,
            plan: $plan,
            planVersion: $planVersion,
            assignment: null,
            features: $features,
            addons: $addons,
            usage: $usage,
            trial: TrialResult::none(),
            account: (array) ($input['account'] ?? []),
        ), [
            'features' => $features,
            'addons' => $addons,
            'usage' => $usage,
            'display_pricing' => [
                'base' => $planVersion->display_price === null ? null : (float) $planVersion->display_price,
                'currency' => $planVersion->currency,
                'discounts' => [],
            ],
            'warnings' => $warnings,
            'blocks' => [],
        ]);

        return new PreviewResult(
            planCode: $plan->code,
            version: (int) $planVersion->version,
            features: $state['features'],
            addons: $state['addons'],
            usage: $state['usage'],
            trial: $trial,
            displayPricing: $state['display_pricing'],
            appliedRules: $state['applied_rules'] ?? [],
            warnings: $state['warnings'] ?? [],
            blocks: $state['blocks'] ?? [],
        );
    }

    private function trialPreview(?Model $subject, array $trial): ?array
    {
        if ($trial === []) {
            return null;
        }

        $blocks = [];
        if (! config('plan-manager.trials.enabled', true)) {
            $blocks[] = 'Trials are disabled';
        }

        if ($subject && ! config('plan-manager.trials.allow_multiple_trials_per_subject', false)) {
            $used = SubscriptionAssignment::query()
                ->where('subject_type', $subject->getMorphClass())
                ->where('subject_id', (string) $subject->getKey())
                ->whereNotNull('trial_type')
                ->exists();
            if ($used) {
                $blocks[] = 'Subject has already used trial';
            }
        }

        $type = $trial['type'] ?? null;
        if ($type && in_array($type, [TrialType::TimeLimited->value, TrialType::TimeAndUsageLimited->value], true) && empty($trial['days'])) {
            $blocks[] = 'Time trial requires days';
        }
        if ($type && in_array($type, [TrialType::UsageLimited->value, TrialType::TimeAndUsageLimited->value], true) && empty($trial['usage_limits'])) {
            $blocks[] = 'Usage trial requires usage_limits';
        }

        return [
            'available' => $blocks === [],
            'trial_type' => $type,
            'days' => $trial['days'] ?? null,
            'usage_limits' => $trial['usage_limits'] ?? [],
            'estimated_ends_at' => isset($trial['days']) ? now()->addDays((int) $trial['days'])->toIso8601String() : null,
            'warnings' => [],
            'blocks' => $blocks,
        ];
    }
}
