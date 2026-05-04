<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Actions;

use FrozonFreak\PlanManager\Enums\SubscriptionStatus;
use FrozonFreak\PlanManager\Models\PlanAuditLog;
use FrozonFreak\PlanManager\Models\SubscriptionAssignment;
use FrozonFreak\PlanManager\Support\EntitlementCache;
use Illuminate\Database\Eloquent\Model;

final class ConvertTrial
{
    public function handle(Model $subject, array $options = []): SubscriptionAssignment
    {
        $assignment = SubscriptionAssignment::query()->currentFor($subject)->trialing()->latest('id')->firstOrFail();

        $assignment->forceFill([
            'status' => SubscriptionStatus::Active,
            'trial_ended_at' => now(),
            'trial_end_reason' => 'converted',
            'external_subscription_id' => $options['external_subscription_id'] ?? $assignment->external_subscription_id,
            'billing_provider' => $options['billing_provider'] ?? $assignment->billing_provider,
        ])->save();

        PlanAuditLog::query()->create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => (string) $subject->getKey(),
            'event' => 'trial.converted',
            'new_values' => ['status' => 'active'],
        ]);

        EntitlementCache::forget($subject);

        return $assignment->refresh();
    }
}
