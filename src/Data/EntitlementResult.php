<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Data;

final class EntitlementResult
{
    public function __construct(
        public readonly ?string $planCode,
        public readonly ?int $planVersion,
        public readonly ?string $status,
        public readonly TrialResult $trial,
        public readonly array $features = [],
        public readonly array $addons = [],
        public readonly array $usage = [],
        public readonly array $appliedRules = [],
        public readonly array $metadata = [],
    ) {}

    public static function empty(): self
    {
        return new self(null, null, null, TrialResult::none());
    }

    public function toArray(): array
    {
        return [
            'plan' => $this->planCode,
            'version' => $this->planVersion,
            'status' => $this->status,
            'trial' => $this->trial->toArray(),
            'features' => $this->features,
            'addons' => $this->addons,
            'usage' => $this->usage,
            'applied_rules' => $this->appliedRules,
            'metadata' => $this->metadata,
        ];
    }
}
