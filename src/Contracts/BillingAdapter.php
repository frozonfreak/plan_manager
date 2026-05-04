<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Contracts;

use FrozonFreak\PlanManager\Models\SubscriptionAssignment;
use Illuminate\Database\Eloquent\Model;

interface BillingAdapter
{
    public function syncSubscription(Model $subject): ?SubscriptionAssignment;

    public function applyPlanChange(Model $subject, SubscriptionAssignment $assignment): void;

    public function supports(): array;
}
