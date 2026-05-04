<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Contracts;

use FrozonFreak\PlanManager\Data\RuleEvaluationContext;

interface RuleConditionEvaluator
{
    public function passes(?array $conditions, RuleEvaluationContext $context): bool;
}
