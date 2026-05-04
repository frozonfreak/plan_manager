<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Actions;

use Carbon\Carbon;
use FrozonFreak\PlanManager\Data\UsageResult;
use FrozonFreak\PlanManager\Enums\UsageResetPeriod;
use FrozonFreak\PlanManager\Models\UsageMeter;
use FrozonFreak\PlanManager\Models\UsageRecord;
use Illuminate\Database\Eloquent\Model;

final class GetUsage
{
    public function handle(Model $subject, string $meterCode, ?float $limit = null): UsageResult
    {
        $meter = UsageMeter::query()->where('code', $meterCode)->firstOrFail();
        [$start, $end] = self::periodFor($meter);

        $used = (float) UsageRecord::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', (string) $subject->getKey())
            ->where('usage_meter_id', $meter->id)
            ->when($start, fn ($query) => $query->where('period_start', $start))
            ->sum('quantity');

        return new UsageResult($used, $limit, $start, $end);
    }

    /** @return array{0:?Carbon,1:?Carbon} */
    public static function periodFor(UsageMeter $meter): array
    {
        $now = now();

        return match ($meter->reset_period) {
            UsageResetPeriod::Never => [null, null],
            UsageResetPeriod::Daily => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            UsageResetPeriod::Monthly => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            UsageResetPeriod::Yearly => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
        };
    }
}
