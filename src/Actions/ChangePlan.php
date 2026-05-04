<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Actions;

use FrozonFreak\PlanManager\Contracts\BillingAdapter;
use FrozonFreak\PlanManager\Models\SubscriptionAssignment;
use Illuminate\Database\Eloquent\Model;

final class ChangePlan
{
    public function __construct(private readonly AssignPlan $assignPlan, private readonly BillingAdapter $billing) {}

    public function handle(Model $subject, string $planCode, ?int $version = null, array $options = []): SubscriptionAssignment
    {
        $assignment = $this->assignPlan->handle($subject, $planCode, $version, $options);
        $this->billing->applyPlanChange($subject, $assignment);

        return $assignment;
    }
}
