<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Data;

use Carbon\Carbon;

final class UsageResult
{
    public function __construct(
        private readonly float $used,
        private readonly ?float $limit,
        private readonly ?Carbon $periodStart,
        private readonly ?Carbon $periodEnd,
    ) {}

    public function used(): float
    {
        return $this->used;
    }

    public function limit(): ?float
    {
        return $this->limit;
    }

    public function remaining(): ?float
    {
        return $this->isUnlimited() ? null : ($this->limit - $this->used);
    }

    public function isUnlimited(): bool
    {
        return $this->limit === null || $this->limit < 0;
    }

    public function exceeded(): bool
    {
        return ! $this->isUnlimited() && $this->used > $this->limit;
    }

    public function periodStart(): ?Carbon
    {
        return $this->periodStart;
    }

    public function periodEnd(): ?Carbon
    {
        return $this->periodEnd;
    }

    public function toArray(): array
    {
        return [
            'used' => $this->used,
            'limit' => $this->limit,
            'remaining' => $this->remaining(),
            'unlimited' => $this->isUnlimited(),
            'exceeded' => $this->exceeded(),
            'period_start' => $this->periodStart?->toIso8601String(),
            'period_end' => $this->periodEnd?->toIso8601String(),
        ];
    }
}
