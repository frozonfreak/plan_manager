<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Contracts;

use FrozonFreak\PlanManager\Data\RuleEvaluationContext;

interface RuleActionApplier
{
    public function apply(array $actions, RuleEvaluationContext $context, array $state): array;
}
