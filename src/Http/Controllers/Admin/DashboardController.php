<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Http\Controllers\Admin;

use FrozonFreak\PlanManager\Models\Plan;
use FrozonFreak\PlanManager\Models\PlanChangeRequest;
use FrozonFreak\PlanManager\Models\PlanRule;
use FrozonFreak\PlanManager\Models\UsageCorrection;
use Illuminate\Contracts\View\View;

final class DashboardController
{
    public function __invoke(): View
    {
        return view('plan-manager::admin.dashboard', [
            'plansCount' => Plan::query()->count(),
            'rulesCount' => PlanRule::query()->count(),
            'pendingApprovalsCount' => PlanChangeRequest::query()->pending()->count(),
            'correctionsCount' => UsageCorrection::query()->count(),
        ]);
    }
}
