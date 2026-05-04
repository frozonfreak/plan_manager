<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager;

use FrozonFreak\PlanManager\Actions\ConsumeUsage;
use FrozonFreak\PlanManager\Actions\GetUsage;
use FrozonFreak\PlanManager\Actions\ResolveEntitlements;
use FrozonFreak\PlanManager\Data\EntitlementResult;
use FrozonFreak\PlanManager\Data\TrialResult;
use FrozonFreak\PlanManager\Data\UsageResult;
use FrozonFreak\PlanManager\Exceptions\EntitlementDeniedException;
use FrozonFreak\PlanManager\Exceptions\TrialExpiredException;
use FrozonFreak\PlanManager\Exceptions\UsageLimitExceededException;
use FrozonFreak\PlanManager\Models\Plan;
use FrozonFreak\PlanManager\Models\PlanVersion;
use FrozonFreak\PlanManager\Models\SubscriptionAssignment;
use FrozonFreak\PlanManager\Models\UsageRecord;
use Illuminate\Database\Eloquent\Model;

final class PlanContext
{
    public function __construct(
        private readonly Model $subject,
        private readonly ResolveEntitlements $resolve,
        private readonly ConsumeUsage $consumeUsage,
        private readonly GetUsage $getUsage,
    ) {}

    public function plan(): ?Plan
    {
        return $this->assignment()?->plan;
    }

    public function planVersion(): ?PlanVersion
    {
        return $this->assignment()?->planVersion;
    }

    public function assignment(): ?SubscriptionAssignment
    {
        return SubscriptionAssignment::query()->with(['plan', 'planVersion'])->currentFor($this->subject)->latest('id')->first();
    }

    public function entitlements(bool $fresh = false): EntitlementResult
    {
        return $this->resolve->handle($this->subject, $fresh);
    }

    public function can(string $featureCode): bool
    {
        return (bool) $this->value($featureCode, false);
    }

    public function value(string $featureCode, mixed $default = null): mixed
    {
        return $this->entitlements()->features[$featureCode] ?? $default;
    }

    public function limit(string $featureCode, mixed $default = null): mixed
    {
        return $this->value($featureCode, $default);
    }

    public function usage(string $meterCode): UsageResult
    {
        $entitlements = $this->entitlements();
        $limit = $entitlements->usage[$meterCode]['limit'] ?? null;

        return $this->getUsage->handle($this->subject, $meterCode, $limit === null ? null : (float) $limit);
    }

    public function consume(string $meterCode, float|int $quantity = 1, array $metadata = []): UsageRecord
    {
        return $this->consumeUsage->handle($this->subject, $meterCode, $quantity, $metadata);
    }

    public function remaining(string $meterCode): float|int|null
    {
        return $this->usage($meterCode)->remaining();
    }

    public function allowsAddon(string $addonCode): bool
    {
        return (bool) ($this->entitlements()->addons[$addonCode] ?? false);
    }

    public function ensureCan(string $featureCode): void
    {
        if (! $this->can($featureCode)) {
            throw new EntitlementDeniedException("Feature [{$featureCode}] is not available.");
        }
    }

    public function ensureWithinLimit(string $featureCode, float|int $newValue): void
    {
        $limit = $this->limit($featureCode);
        if ($limit !== null && (float) $limit >= 0 && (float) $newValue > (float) $limit) {
            throw new UsageLimitExceededException("Value exceeds plan limit [{$featureCode}].");
        }
    }

    public function isTrialing(): bool
    {
        return $this->trial()->isActive();
    }

    public function trial(): TrialResult
    {
        return $this->entitlements(true)->trial;
    }

    public function trialExpired(): bool
    {
        return $this->trial()->isExpired();
    }

    public function trialRemainingDays(): ?int
    {
        return $this->trial()->remainingDays();
    }

    public function trialUsageRemaining(string $meterCode): ?float
    {
        return $this->trial()->remainingUsage($meterCode);
    }

    public function ensureTrialActive(): void
    {
        if (! $this->trial()->isActive()) {
            throw new TrialExpiredException('Trial is not active.');
        }
    }
}
