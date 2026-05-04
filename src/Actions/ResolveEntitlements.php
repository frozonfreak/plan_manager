<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Actions;

use FrozonFreak\PlanManager\Data\EntitlementResult;
use FrozonFreak\PlanManager\Data\RuleEvaluationContext;
use FrozonFreak\PlanManager\Data\TrialResult;
use FrozonFreak\PlanManager\Enums\SubscriptionStatus;
use FrozonFreak\PlanManager\Enums\TrialEndReason;
use FrozonFreak\PlanManager\Enums\TrialType;
use FrozonFreak\PlanManager\Models\EntitlementSnapshot;
use FrozonFreak\PlanManager\Models\PlanAuditLog;
use FrozonFreak\PlanManager\Models\SubscriptionAssignment;
use FrozonFreak\PlanManager\Models\UsageMeter;
use FrozonFreak\PlanManager\Models\UsageRecord;
use FrozonFreak\PlanManager\Support\EntitlementCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

final class ResolveEntitlements
{
    public function __construct(private readonly EvaluateRules $rules) {}

    public function handle(Model $subject, bool $fresh = false): EntitlementResult
    {
        $key = EntitlementCache::key($subject);

        if (! $fresh && config('plan-manager.cache.enabled', true)) {
            $cached = Cache::store(config('plan-manager.cache.store'))->get($key);
            if ($cached instanceof EntitlementResult) {
                return $cached;
            }
        }

        $assignment = SubscriptionAssignment::query()
            ->with(['plan', 'planVersion.featureValues.feature', 'planVersion.addons'])
            ->currentFor($subject)
            ->latest('id')
            ->first();

        if (! $assignment) {
            return EntitlementResult::empty();
        }

        $trial = $this->trialResult($assignment);

        if ($assignment->status === SubscriptionStatus::Trialing && $trial->expired && config('plan-manager.trials.auto_expire_trials_during_resolution', true)) {
            $assignment->forceFill([
                'status' => SubscriptionStatus::Expired,
                'trial_ended_at' => $assignment->trial_ended_at ?? now(),
                'trial_end_reason' => $trial->expiryReason,
                'ends_at' => now(),
            ])->save();

            PlanAuditLog::query()->create([
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => (string) $subject->getKey(),
                'event' => 'trial.expired',
                'new_values' => ['reason' => $trial->expiryReason],
            ]);
        }

        $features = [];
        foreach ($assignment->planVersion->featureValues as $value) {
            $features[$value->feature->code] = $value->castedValue();
        }

        $addons = [];
        foreach ($assignment->planVersion->addons as $addon) {
            $addons[$addon->code] = (bool) $addon->pivot->is_available;
        }

        $usage = $this->usageSummary($subject, $features);

        $state = $this->rules->handle(new RuleEvaluationContext(
            subject: $subject,
            plan: $assignment->plan,
            planVersion: $assignment->planVersion,
            assignment: $assignment,
            features: $features,
            addons: $addons,
            usage: $usage,
            trial: $trial,
            account: (array) ($assignment->metadata['account'] ?? []),
        ), ['features' => $features, 'addons' => $addons, 'usage' => $usage]);

        $result = new EntitlementResult(
            planCode: $assignment->plan->code,
            planVersion: (int) $assignment->planVersion->version,
            status: $assignment->status->value,
            trial: $trial,
            features: $state['features'],
            addons: $state['addons'],
            usage: $state['usage'],
            appliedRules: $state['applied_rules'] ?? [],
            metadata: ['assignment_id' => $assignment->id],
        );

        EntitlementSnapshot::query()->create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => (string) $subject->getKey(),
            'subscription_assignment_id' => $assignment->id,
            'plan_id' => $assignment->plan_id,
            'plan_version_id' => $assignment->plan_version_id,
            'entitlements' => $result->toArray(),
            'usage_summary' => $usage,
            'trial' => $trial->toArray(),
            'addons' => $state['addons'],
            'resolved_at' => now(),
        ]);

        if (config('plan-manager.cache.enabled', true)) {
            Cache::store(config('plan-manager.cache.store'))->put($key, $result, (int) config('plan-manager.cache.ttl_seconds', 300));
        }

        return $result;
    }

    private function trialResult(SubscriptionAssignment $assignment): TrialResult
    {
        if ($assignment->status !== SubscriptionStatus::Trialing) {
            return TrialResult::none();
        }

        $limits = (array) $assignment->trial_usage_limits;
        $consumed = (array) $assignment->trial_usage_consumed;
        $remaining = [];
        foreach ($limits as $meter => $limit) {
            $remaining[$meter] = max(0, (float) $limit - (float) ($consumed[$meter] ?? 0));
        }

        $reason = null;
        $expired = false;
        $type = $assignment->trial_type;

        if ($assignment->trial_ended_at !== null) {
            $expired = true;
            $reason = $assignment->trial_end_reason ?: 'manual_end';
        } elseif (in_array($type, [TrialType::TimeLimited, TrialType::TimeAndUsageLimited], true)
            && $assignment->trial_ends_at !== null
            && now()->greaterThanOrEqualTo($assignment->trial_ends_at)) {
            $expired = true;
            $reason = TrialEndReason::TimeExpired->value;
        } elseif (in_array($type, [TrialType::UsageLimited, TrialType::TimeAndUsageLimited], true)) {
            foreach ($limits as $meter => $limit) {
                if ((float) ($consumed[$meter] ?? 0) >= (float) $limit) {
                    $expired = true;
                    $reason = TrialEndReason::UsageExhausted->value;
                    break;
                }
            }
        }

        return new TrialResult(
            isTrialing: true,
            trialType: $type?->value,
            startedAt: $assignment->trial_started_at,
            endsAt: $assignment->trial_ends_at,
            endedAt: $assignment->trial_ended_at,
            endReason: $assignment->trial_end_reason,
            usageLimits: $limits,
            usageConsumed: $consumed,
            usageRemaining: $remaining,
            expired: $expired,
            expiryReason: $reason,
        );
    }

    private function usageSummary(Model $subject, array $features): array
    {
        $summary = [];
        foreach (UsageMeter::query()->get() as $meter) {
            [$start, $end] = GetUsage::periodFor($meter);
            $used = (float) UsageRecord::query()
                ->where('subject_type', $subject->getMorphClass())
                ->where('subject_id', (string) $subject->getKey())
                ->where('usage_meter_id', $meter->id)
                ->when($start, fn ($query) => $query->where('period_start', $start))
                ->sum('quantity');

            $limit = $features[$meter->code.'.monthly'] ?? null;
            $limit = is_numeric($limit) ? (float) $limit : null;
            $remaining = $limit === null || $limit < 0 ? null : $limit - $used;

            $summary[$meter->code] = [
                'used' => $used,
                'limit' => $limit,
                'remaining' => $remaining,
                'exceeded' => $limit !== null && $limit >= 0 && $used > $limit,
                'period_start' => $start?->toIso8601String(),
                'period_end' => $end?->toIso8601String(),
            ];
        }

        return $summary;
    }
}
