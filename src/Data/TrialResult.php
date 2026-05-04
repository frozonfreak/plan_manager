<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Data;

use Carbon\Carbon;

final class TrialResult
{
    public function __construct(
        public readonly bool $isTrialing,
        public readonly ?string $trialType = null,
        public readonly ?Carbon $startedAt = null,
        public readonly ?Carbon $endsAt = null,
        public readonly ?Carbon $endedAt = null,
        public readonly ?string $endReason = null,
        public readonly array $usageLimits = [],
        public readonly array $usageConsumed = [],
        public readonly array $usageRemaining = [],
        public readonly bool $expired = false,
        public readonly ?string $expiryReason = null,
    ) {}

    public static function none(): self
    {
        return new self(false);
    }

    public function isActive(): bool
    {
        return $this->isTrialing && ! $this->expired && $this->endedAt === null;
    }

    public function isExpired(): bool
    {
        return $this->expired;
    }

    public function remainingDays(): ?int
    {
        if (! $this->endsAt || $this->expired) {
            return null;
        }

        return (int) max(0, now()->startOfDay()->diffInDays($this->endsAt, false));
    }

    public function remainingUsage(string $meterCode): ?float
    {
        return array_key_exists($meterCode, $this->usageRemaining) ? (float) $this->usageRemaining[$meterCode] : null;
    }

    public function toArray(): array
    {
        return [
            'is_trialing' => $this->isTrialing,
            'trial_type' => $this->trialType,
            'trial_started_at' => $this->startedAt?->toIso8601String(),
            'trial_ends_at' => $this->endsAt?->toIso8601String(),
            'trial_ended_at' => $this->endedAt?->toIso8601String(),
            'trial_end_reason' => $this->endReason,
            'trial_expired' => $this->expired,
            'expiry_reason' => $this->expiryReason,
            'trial_usage_limits' => $this->usageLimits,
            'trial_usage_consumed' => $this->usageConsumed,
            'trial_usage_remaining' => $this->usageRemaining,
        ];
    }
}
