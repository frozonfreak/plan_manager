<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Actions;

use FrozonFreak\PlanManager\Enums\SubscriptionStatus;
use FrozonFreak\PlanManager\Enums\TrialEndReason;
use FrozonFreak\PlanManager\Enums\TrialType;
use FrozonFreak\PlanManager\Models\PlanAuditLog;
use FrozonFreak\PlanManager\Models\SubscriptionAssignment;
use FrozonFreak\PlanManager\Support\EntitlementCache;
use Illuminate\Support\Collection;

final class ExpireTrials
{
    public function handle(bool $dryRun = false, ?string $subjectType = null, ?string $subjectId = null): Collection
    {
        $expired = collect();

        SubscriptionAssignment::query()
            ->trialing()
            ->when($subjectType, fn ($query) => $query->where('subject_type', $subjectType))
            ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
            ->chunkById(100, function ($assignments) use ($dryRun, $expired): void {
                foreach ($assignments as $assignment) {
                    $reason = $this->expiryReason($assignment);
                    if (! $reason) {
                        continue;
                    }

                    $expired->push(['assignment_id' => $assignment->id, 'reason' => $reason]);

                    if ($dryRun) {
                        continue;
                    }

                    $assignment->forceFill([
                        'status' => SubscriptionStatus::Expired,
                        'trial_ended_at' => now(),
                        'trial_end_reason' => $reason,
                        'ends_at' => now(),
                    ])->save();

                    PlanAuditLog::query()->create([
                        'subject_type' => $assignment->subject_type,
                        'subject_id' => $assignment->subject_id,
                        'event' => 'trial.expired',
                        'new_values' => ['reason' => $reason],
                    ]);

                    EntitlementCache::bumpVersion();
                }
            });

        return $expired;
    }

    private function expiryReason(SubscriptionAssignment $assignment): ?string
    {
        if (in_array($assignment->trial_type, [TrialType::TimeLimited, TrialType::TimeAndUsageLimited], true)
            && $assignment->trial_ends_at !== null
            && now()->greaterThanOrEqualTo($assignment->trial_ends_at)) {
            return TrialEndReason::TimeExpired->value;
        }

        if (in_array($assignment->trial_type, [TrialType::UsageLimited, TrialType::TimeAndUsageLimited], true)) {
            $consumed = (array) $assignment->trial_usage_consumed;
            foreach ((array) $assignment->trial_usage_limits as $meter => $limit) {
                if ((float) ($consumed[$meter] ?? 0) >= (float) $limit) {
                    return TrialEndReason::UsageExhausted->value;
                }
            }
        }

        return $assignment->trial_ended_at ? ($assignment->trial_end_reason ?: 'manual_end') : null;
    }
}
