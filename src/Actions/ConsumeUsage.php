<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Actions;

use FrozonFreak\PlanManager\Enums\SubscriptionStatus;
use FrozonFreak\PlanManager\Exceptions\EntitlementDeniedException;
use FrozonFreak\PlanManager\Exceptions\TrialExpiredException;
use FrozonFreak\PlanManager\Exceptions\TrialUsageLimitExceededException;
use FrozonFreak\PlanManager\Exceptions\UsageLimitExceededException;
use FrozonFreak\PlanManager\Models\PlanAuditLog;
use FrozonFreak\PlanManager\Models\SubscriptionAssignment;
use FrozonFreak\PlanManager\Models\UsageMeter;
use FrozonFreak\PlanManager\Models\UsageRecord;
use FrozonFreak\PlanManager\Support\EntitlementCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class ConsumeUsage
{
    public function __construct(private readonly ResolveEntitlements $entitlements) {}

    public function handle(Model $subject, string $meterCode, float|int $quantity = 1, array $metadata = []): UsageRecord
    {
        return DB::transaction(function () use ($subject, $meterCode, $quantity, $metadata): UsageRecord {
            $meter = UsageMeter::query()->where('code', $meterCode)->firstOrFail();
            $entitlements = $this->entitlements->handle($subject, true);
            $assignment = SubscriptionAssignment::query()->currentFor($subject)->latest('id')->lockForUpdate()->first();

            if (! $assignment) {
                $previousTrial = SubscriptionAssignment::query()
                    ->where('subject_type', $subject->getMorphClass())
                    ->where('subject_id', (string) $subject->getKey())
                    ->whereNotNull('trial_type')
                    ->latest('id')
                    ->first();

                $limits = (array) $previousTrial?->trial_usage_limits;
                $consumed = (array) $previousTrial?->trial_usage_consumed;
                if (array_key_exists($meterCode, $limits) && (float) ($consumed[$meterCode] ?? 0) >= (float) $limits[$meterCode]) {
                    throw new TrialUsageLimitExceededException("Trial usage limit for [{$meterCode}] has been exhausted.");
                }

                throw new EntitlementDeniedException('No active plan assignment is available for usage consumption.');
            }

            if ($assignment?->status === SubscriptionStatus::Trialing) {
                if ($entitlements->trial->isExpired()) {
                    throw new TrialExpiredException('Trial has expired.');
                }

                $limits = (array) $assignment->trial_usage_limits;
                if (array_key_exists($meterCode, $limits)) {
                    $consumed = (array) $assignment->trial_usage_consumed;
                    $current = (float) ($consumed[$meterCode] ?? 0);
                    $limit = (float) $limits[$meterCode];
                    $next = $current + (float) $quantity;
                    $allowsExact = (bool) config('plan-manager.trials.allow_exact_limit_consumption', true);
                    $exceeds = $allowsExact ? $next > $limit : $next >= $limit;

                    if ($exceeds) {
                        throw new TrialUsageLimitExceededException("Trial usage limit for [{$meterCode}] would be exceeded.");
                    }
                }
            }

            $usage = $entitlements->usage[$meterCode] ?? null;
            if (config('plan-manager.usage.strict_limits', true) && is_array($usage) && $usage['limit'] !== null && (float) $usage['limit'] >= 0) {
                $nextUsed = (float) $usage['used'] + (float) $quantity;
                if ($nextUsed > (float) $usage['limit']) {
                    throw new UsageLimitExceededException("Usage limit for [{$meterCode}] would be exceeded.");
                }
            }

            [$start, $end] = GetUsage::periodFor($meter);

            $record = UsageRecord::query()->create([
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => (string) $subject->getKey(),
                'usage_meter_id' => $meter->id,
                'quantity' => $quantity,
                'period_start' => $start,
                'period_end' => $end,
                'source' => $metadata['source'] ?? null,
                'reference' => $metadata['reference'] ?? null,
                'metadata' => $metadata,
            ]);

            if ($assignment?->status === SubscriptionStatus::Trialing) {
                $limits = (array) $assignment->trial_usage_limits;
                if (array_key_exists($meterCode, $limits)) {
                    $consumed = (array) $assignment->trial_usage_consumed;
                    $consumed[$meterCode] = (float) ($consumed[$meterCode] ?? 0) + (float) $quantity;

                    $updates = ['trial_usage_consumed' => $consumed];
                    if (config('plan-manager.trials.expire_on_first_usage_limit_reached', true)
                        && (float) $consumed[$meterCode] >= (float) $limits[$meterCode]) {
                        $updates += [
                            'status' => SubscriptionStatus::Expired,
                            'trial_ended_at' => now(),
                            'trial_end_reason' => 'usage_exhausted',
                            'ends_at' => now(),
                        ];
                    }

                    $assignment->forceFill($updates)->save();
                }
            }

            PlanAuditLog::query()->create([
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => (string) $subject->getKey(),
                'event' => 'usage.consumed',
                'new_values' => ['meter' => $meterCode, 'quantity' => $quantity],
            ]);

            EntitlementCache::forget($subject);

            return $record;
        });
    }
}
