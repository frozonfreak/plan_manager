<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Rules\Operators;

final class InOperator
{
    public function __invoke(mixed $actual, mixed $expected): bool
    {
        return in_array($actual, (array) $expected, true);
    }
}
