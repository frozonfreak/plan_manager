<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Rules\Operators;

final class GreaterThanOperator
{
    public function __invoke(mixed $actual, mixed $expected): bool
    {
        return (float) $actual > (float) $expected;
    }
}
