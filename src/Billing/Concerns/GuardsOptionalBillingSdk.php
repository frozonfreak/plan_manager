<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Billing\Concerns;

use FrozonFreak\PlanManager\Exceptions\BillingAdapterException;

trait GuardsOptionalBillingSdk
{
    private function requireAnyClass(string $adapter, array $classes): void
    {
        foreach ($classes as $class) {
            if (class_exists($class)) {
                return;
            }
        }

        throw new BillingAdapterException("{$adapter} was selected, but its SDK/client package is not installed. This adapter only syncs subscription metadata; billing remains outside Plan Manager.");
    }
}
