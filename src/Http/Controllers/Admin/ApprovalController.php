<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Http\Controllers\Admin;

use FrozonFreak\PlanManager\Facades\PlanManager;
use FrozonFreak\PlanManager\Models\PlanChangeRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ApprovalController
{
    public function index(): View
    {
        return view('plan-manager::admin.approvals.index', [
            'requests' => PlanChangeRequest::query()->with(['targetPlan', 'targetPlanVersion'])->latest()->paginate(20),
        ]);
    }

    public function approve(Request $request, PlanChangeRequest $planChangeRequest): RedirectResponse
    {
        PlanManager::approveChange($planChangeRequest, $request->user(), $request->input('note'));

        return back()->with('status', 'Plan change approved.');
    }

    public function reject(Request $request, PlanChangeRequest $planChangeRequest): RedirectResponse
    {
        PlanManager::rejectChange($planChangeRequest, $request->user(), $request->input('note'));

        return back()->with('status', 'Plan change rejected.');
    }

    public function apply(PlanChangeRequest $planChangeRequest): RedirectResponse
    {
        PlanManager::applyApprovedChange($planChangeRequest);

        return back()->with('status', 'Approved plan change applied.');
    }
}
