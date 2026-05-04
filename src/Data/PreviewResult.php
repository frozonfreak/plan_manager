<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Data;

final class PreviewResult
{
    public function __construct(
        public readonly string $planCode,
        public readonly ?int $version,
        public readonly array $features = [],
        public readonly array $addons = [],
        public readonly array $usage = [],
        public readonly ?array $trial = null,
        public readonly array $displayPricing = [],
        public readonly array $appliedRules = [],
        public readonly array $warnings = [],
        public readonly array $blocks = [],
    ) {}

    public function toArray(): array
    {
        return [
            'plan' => $this->planCode,
            'version' => $this->version,
            'features' => $this->features,
            'addons' => $this->addons,
            'usage' => $this->usage,
            'trial' => $this->trial,
            'display_pricing' => $this->displayPricing,
            'applied_rules' => $this->appliedRules,
            'warnings' => $this->warnings,
            'blocks' => $this->blocks,
        ];
    }
}
