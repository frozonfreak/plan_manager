<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Billing;

use FrozonFreak\PlanManager\Contracts\BillingAdapter;
use FrozonFreak\PlanManager\Models\SubscriptionAssignment;
use Illuminate\Database\Eloquent\Model;

class NullBillingAdapter implements BillingAdapter
{
    public function syncSubscription(Model $subject): ?SubscriptionAssignment
    {
        return null;
    }

    public function applyPlanChange(Model $subject, SubscriptionAssignment $assignment): void {}

    public function supports(): array
    {
        return ['local_trials', 'local_entitlements'];
    }
}
