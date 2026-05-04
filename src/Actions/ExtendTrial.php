<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Actions;

use FrozonFreak\PlanManager\Models\PlanAuditLog;
use FrozonFreak\PlanManager\Models\SubscriptionAssignment;
use FrozonFreak\PlanManager\Support\EntitlementCache;
use Illuminate\Database\Eloquent\Model;

final class ExtendTrial
{
    public function handle(Model $subject, int $days): SubscriptionAssignment
    {
        $assignment = SubscriptionAssignment::query()->currentFor($subject)->trialing()->latest('id')->firstOrFail();
        $base = $assignment->trial_ends_at && $assignment->trial_ends_at->isFuture() ? $assignment->trial_ends_at : now();

        $assignment->forceFill(['trial_ends_at' => $base->copy()->addDays($days)])->save();

        PlanAuditLog::query()->create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => (string) $subject->getKey(),
            'event' => 'trial.extended',
            'new_values' => ['days' => $days, 'trial_ends_at' => $assignment->trial_ends_at?->toIso8601String()],
        ]);

        EntitlementCache::forget($subject);

        return $assignment->refresh();
    }
}
