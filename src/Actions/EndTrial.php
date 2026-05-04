<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Actions;

use FrozonFreak\PlanManager\Enums\SubscriptionStatus;
use FrozonFreak\PlanManager\Models\PlanAuditLog;
use FrozonFreak\PlanManager\Models\SubscriptionAssignment;
use FrozonFreak\PlanManager\Support\EntitlementCache;
use Illuminate\Database\Eloquent\Model;

final class EndTrial
{
    public function handle(Model $subject, string $reason = 'manual_end'): SubscriptionAssignment
    {
        $assignment = SubscriptionAssignment::query()->currentFor($subject)->trialing()->latest('id')->firstOrFail();

        $assignment->forceFill([
            'status' => SubscriptionStatus::Expired,
            'trial_ended_at' => now(),
            'trial_end_reason' => $reason,
            'ends_at' => now(),
        ])->save();

        PlanAuditLog::query()->create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => (string) $subject->getKey(),
            'event' => 'trial.ended',
            'new_values' => ['reason' => $reason],
        ]);

        $this->forget($subject);

        return $assignment->refresh();
    }

    private function forget(Model $subject): void
    {
        EntitlementCache::forget($subject);
    }
}
