<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Actions;

use FrozonFreak\PlanManager\Enums\SubscriptionStatus;
use FrozonFreak\PlanManager\Exceptions\PlanNotFoundException;
use FrozonFreak\PlanManager\Exceptions\PlanVersionNotFoundException;
use FrozonFreak\PlanManager\Models\Plan;
use FrozonFreak\PlanManager\Models\PlanAuditLog;
use FrozonFreak\PlanManager\Models\SubscriptionAssignment;
use FrozonFreak\PlanManager\Support\EntitlementCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class AssignPlan
{
    public function handle(Model $subject, string $planCode, ?int $version = null, array $options = []): SubscriptionAssignment
    {
        return DB::transaction(function () use ($subject, $planCode, $version, $options): SubscriptionAssignment {
            $plan = Plan::query()->where('code', $planCode)->first();
            if (! $plan) {
                throw new PlanNotFoundException("Plan [{$planCode}] was not found.");
            }

            $planVersion = $plan->activeVersion($version, $options['billing_cycle'] ?? null);
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
                'status' => $options['status'] ?? SubscriptionStatus::Active->value,
                'billing_cycle' => $options['billing_cycle'] ?? $planVersion->billing_cycle,
                'starts_at' => $options['starts_at'] ?? now(),
                'external_subscription_id' => $options['external_subscription_id'] ?? null,
                'billing_provider' => $options['billing_provider'] ?? null,
                'metadata' => $options['metadata'] ?? null,
            ]);

            PlanAuditLog::query()->create([
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => (string) $subject->getKey(),
                'event' => 'plan.assigned',
                'new_values' => ['plan' => $planCode, 'version' => $planVersion->version],
            ]);

            $this->forget($subject);

            return $assignment->refresh();
        });
    }

    private function forget(Model $subject): void
    {
        EntitlementCache::forget($subject);
    }
}
