<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Actions;

use FrozonFreak\PlanManager\Enums\UsageCorrectionType;
use FrozonFreak\PlanManager\Exceptions\UsageCorrectionException;
use FrozonFreak\PlanManager\Models\PlanAuditLog;
use FrozonFreak\PlanManager\Models\UsageCorrection;
use FrozonFreak\PlanManager\Models\UsageMeter;
use FrozonFreak\PlanManager\Models\UsageRecord;
use FrozonFreak\PlanManager\Support\EntitlementCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class CorrectUsage
{
    public function __construct(private readonly GetUsage $usage) {}

    public function handle(
        Model $subject,
        string $meterCode,
        float|int $quantity,
        string $reason,
        array $options = [],
    ): UsageCorrection {
        if (trim($reason) === '') {
            throw new UsageCorrectionException('Usage corrections require a reason.');
        }

        return DB::transaction(function () use ($subject, $meterCode, $quantity, $reason, $options): UsageCorrection {
            $meter = UsageMeter::query()->where('code', $meterCode)->firstOrFail();
            $type = UsageCorrectionType::from($options['type'] ?? UsageCorrectionType::Adjustment->value);
            [$start, $end] = GetUsage::periodFor($meter);

            $adjustment = (float) $quantity;
            if ($type === UsageCorrectionType::SetTo) {
                $current = $this->usage->handle($subject, $meterCode)->used();
                $adjustment = (float) $quantity - $current;
            }

            $record = UsageRecord::query()->create([
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => (string) $subject->getKey(),
                'usage_meter_id' => $meter->id,
                'quantity' => $adjustment,
                'period_start' => $start,
                'period_end' => $end,
                'source' => 'correction',
                'reference' => $options['reference'] ?? null,
                'metadata' => [
                    'reason' => $reason,
                    'correction_type' => $type->value,
                ] + (array) ($options['metadata'] ?? []),
            ]);

            $actor = $options['actor'] ?? null;
            $correction = UsageCorrection::query()->create([
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => (string) $subject->getKey(),
                'usage_meter_id' => $meter->id,
                'usage_record_id' => $options['usage_record_id'] ?? null,
                'correction_type' => $type,
                'quantity' => $adjustment,
                'reason' => $reason,
                'actor_type' => $actor instanceof Model ? $actor->getMorphClass() : null,
                'actor_id' => $actor instanceof Model ? (string) $actor->getKey() : null,
                'resulting_usage_record_id' => $record->id,
                'metadata' => $options['metadata'] ?? null,
            ]);

            PlanAuditLog::query()->create([
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => (string) $subject->getKey(),
                'actor_type' => $actor instanceof Model ? $actor->getMorphClass() : null,
                'actor_id' => $actor instanceof Model ? (string) $actor->getKey() : null,
                'event' => 'usage.corrected',
                'new_values' => [
                    'meter' => $meterCode,
                    'quantity' => $adjustment,
                    'type' => $type->value,
                    'reason' => $reason,
                ],
            ]);

            EntitlementCache::forget($subject);

            return $correction->refresh();
        });
    }
}
