<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Billing;

use FrozonFreak\PlanManager\Billing\Concerns\GuardsOptionalBillingSdk;
use FrozonFreak\PlanManager\Contracts\BillingAdapter;
use FrozonFreak\PlanManager\Models\SubscriptionAssignment;
use Illuminate\Database\Eloquent\Model;

final class RazorpayAdapter implements BillingAdapter
{
    use GuardsOptionalBillingSdk;

    public function syncSubscription(Model $subject): ?SubscriptionAssignment
    {
        $this->ensureAvailable();

        return null;
    }

    public function applyPlanChange(Model $subject, SubscriptionAssignment $assignment): void
    {
        $this->ensureAvailable();
    }

    public function supports(): array
    {
        return ['razorpay_metadata_boundary', 'local_trials', 'local_entitlements'];
    }

    private function ensureAvailable(): void
    {
        $this->requireAnyClass('RazorpayAdapter', ['Razorpay\\Api\\Api']);
    }
}
