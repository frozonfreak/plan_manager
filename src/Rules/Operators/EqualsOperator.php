<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Rules\Operators;

final class EqualsOperator
{
    public function __invoke(mixed $actual, mixed $expected): bool
    {
        return $actual == $expected;
    }
}
