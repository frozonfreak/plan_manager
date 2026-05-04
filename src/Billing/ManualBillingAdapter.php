<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Billing;

final class ManualBillingAdapter extends NullBillingAdapter
{
    public function supports(): array
    {
        return ['manual_billing', 'local_trials', 'local_entitlements'];
    }
}
