<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Data;

use FrozonFreak\PlanManager\Models\Plan;
use FrozonFreak\PlanManager\Models\PlanVersion;
use FrozonFreak\PlanManager\Models\SubscriptionAssignment;
use Illuminate\Database\Eloquent\Model;

final class RuleEvaluationContext
{
    public function __construct(
        public readonly ?Model $subject = null,
        public readonly ?Plan $plan = null,
        public readonly ?PlanVersion $planVersion = null,
        public readonly ?SubscriptionAssignment $assignment = null,
        public readonly array $features = [],
        public readonly array $addons = [],
        public readonly array $usage = [],
        public readonly TrialResult $trial = new TrialResult(false),
        public readonly array $account = [],
        public readonly ?string $addonCode = null,
    ) {}
}
