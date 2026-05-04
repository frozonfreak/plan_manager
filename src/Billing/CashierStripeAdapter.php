<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Billing;

use FrozonFreak\PlanManager\Contracts\BillingAdapter;
use FrozonFreak\PlanManager\Exceptions\BillingAdapterException;
use FrozonFreak\PlanManager\Models\SubscriptionAssignment;
use Illuminate\Database\Eloquent\Model;

final class CashierStripeAdapter implements BillingAdapter
{
    public function syncSubscription(Model $subject): ?SubscriptionAssignment
    {
        $this->ensureCashierAvailable($subject);

        return null;
    }

    public function applyPlanChange(Model $subject, SubscriptionAssignment $assignment): void
    {
        $this->ensureCashierAvailable($subject);

        if (! method_exists($subject, 'subscription')) {
            throw new BillingAdapterException('CashierStripeAdapter requires the subject to expose Cashier subscription methods.');
        }

        // Billing swaps remain an integration boundary; local assignment has already changed.
    }

    public function supports(): array
    {
        return ['cashier_stripe_detected', 'local_trials', 'local_entitlements'];
    }

    private function ensureCashierAvailable(Model $subject): void
    {
        if (! trait_exists('Laravel\\Cashier\\Billable') && ! class_exists('Laravel\\Cashier\\Cashier')) {
            throw new BillingAdapterException('CashierStripeAdapter was selected, but Laravel Cashier is not installed.');
        }

        if (! method_exists($subject, 'subscription')) {
            throw new BillingAdapterException('CashierStripeAdapter does not assume User; configure a billable subject that uses Cashier.');
        }
    }
}
