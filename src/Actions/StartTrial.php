<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Actions;

use FrozonFreak\PlanManager\Enums\SubscriptionStatus;
use FrozonFreak\PlanManager\Enums\TrialType;
use FrozonFreak\PlanManager\Exceptions\InvalidTrialConfigurationException;
use FrozonFreak\PlanManager\Exceptions\PlanNotFoundException;
use FrozonFreak\PlanManager\Exceptions\PlanVersionNotFoundException;
use FrozonFreak\PlanManager\Exceptions\TrialAlreadyUsedException;
use FrozonFreak\PlanManager\Exceptions\TrialNotAvailableException;
use FrozonFreak\PlanManager\Models\Plan;
use FrozonFreak\PlanManager\Models\PlanAuditLog;
use FrozonFreak\PlanManager\Models\SubscriptionAssignment;
use FrozonFreak\PlanManager\Support\EntitlementCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class StartTrial
{
    public function handle(Model $subject, string $planCode, string $trialType, array $options = []): SubscriptionAssignment
    {
        if (! config('plan-manager.trials.enabled', true)) {
            throw new TrialNotAvailableException('Trials are disabled.');
        }

        $type = TrialType::from($trialType);
        $this->validateConfiguration($type, $options);
        $this->ensureEligible($subject);

        return DB::transaction(function () use ($subject, $planCode, $type, $options): SubscriptionAssignment {
            $plan = Plan::query()->where('code', $planCode)->first();
            if (! $plan) {
                throw new PlanNotFoundException("Plan [{$planCode}] was not found.");
            }

            $planVersion = $plan->activeVersion($options['version'] ?? null, $options['billing_cycle'] ?? null);
            if (! $planVersion) {
                throw new PlanVersionNotFoundException("Active version for plan [{$planCode}] was not found.");
            }

            SubscriptionAssignment::query()->currentFor($subject)->update([
                'status' => SubscriptionStatus::Cancelled->value,
                'ends_at' => now(),
            ]);

            $assignment = SubscriptionAssignment::query()->create([
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => (string) $subject->getKey(),
                'plan_id' => $plan->id,
                'plan_version_id' => $planVersion->id,
                'status' => SubscriptionStatus::Trialing,
                'billing_cycle' => $options['billing_cycle'] ?? $planVersion->billing_cycle,
                'starts_at' => now(),
                'trial_type' => $type,
                'trial_started_at' => now(),
                'trial_ends_at' => isset($options['trial_ends_at']) ? $options['trial_ends_at'] : (isset($options['days']) ? now()->addDays((int) $options['days']) : null),
                'trial_usage_limits' => $options['usage_limits'] ?? [],
                'trial_usage_consumed' => [],
                'metadata' => $options['metadata'] ?? null,
            ]);

            PlanAuditLog::query()->create([
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => (string) $subject->getKey(),
                'event' => 'trial.started',
                'new_values' => ['plan' => $planCode, 'trial_type' => $type->value],
            ]);

            $this->forget($subject);

            return $assignment->refresh();
        });
    }

    private function validateConfiguration(TrialType $type, array $options): void
    {
        if (in_array($type, [TrialType::TimeLimited, TrialType::TimeAndUsageLimited], true)
            && empty($options['days']) && empty($options['trial_ends_at'])) {
            throw new InvalidTrialConfigurationException('Time-based trials require days or trial_ends_at.');
        }

        if (in_array($type, [TrialType::UsageLimited, TrialType::TimeAndUsageLimited], true)
            && empty($options['usage_limits'])) {
            throw new InvalidTrialConfigurationException('Usage-based trials require usage_limits.');
        }
    }

    private function ensureEligible(Model $subject): void
    {
        if (! config('plan-manager.trials.allow_multiple_trials_per_subject', false)) {
            $used = SubscriptionAssignment::query()
                ->where('subject_type', $subject->getMorphClass())
                ->where('subject_id', (string) $subject->getKey())
                ->whereNotNull('trial_type')
                ->exists();

            if ($used) {
                throw new TrialAlreadyUsedException('Subject has already used trial.');
            }
        }

        if (! config('plan-manager.trials.allow_trial_after_cancellation', false)) {
            $active = SubscriptionAssignment::query()->currentFor($subject)->exists();
            if ($active) {
                throw new TrialNotAvailableException('Subject already has an active assignment.');
            }
        }
    }

    private function forget(Model $subject): void
    {
        EntitlementCache::forget($subject);
    }
}
