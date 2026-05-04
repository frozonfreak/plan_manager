<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager;

use FrozonFreak\PlanManager\Actions\ApplyApprovedPlanChange;
use FrozonFreak\PlanManager\Actions\ApprovePlanChange;
use FrozonFreak\PlanManager\Actions\AssignPlan;
use FrozonFreak\PlanManager\Actions\ChangePlan;
use FrozonFreak\PlanManager\Actions\ConvertTrial;
use FrozonFreak\PlanManager\Actions\CorrectUsage;
use FrozonFreak\PlanManager\Actions\EndTrial;
use FrozonFreak\PlanManager\Actions\ExtendTrial;
use FrozonFreak\PlanManager\Actions\PreviewPlanChange;
use FrozonFreak\PlanManager\Actions\RecalculateEntitlementSnapshot;
use FrozonFreak\PlanManager\Actions\RejectPlanChange;
use FrozonFreak\PlanManager\Actions\RequestPlanChange;
use FrozonFreak\PlanManager\Actions\StartTrial;
use FrozonFreak\PlanManager\Data\EntitlementResult;
use FrozonFreak\PlanManager\Data\PreviewResult;
use FrozonFreak\PlanManager\Models\PlanChangeRequest;
use FrozonFreak\PlanManager\Models\SubscriptionAssignment;
use FrozonFreak\PlanManager\Models\UsageCorrection;
use Illuminate\Database\Eloquent\Model;

final class PlanManager
{
    public function __construct(
        private readonly PreviewPlanChange $preview,
        private readonly AssignPlan $assignPlan,
        private readonly ChangePlan $changePlan,
        private readonly RecalculateEntitlementSnapshot $recalculate,
        private readonly StartTrial $startTrial,
        private readonly EndTrial $endTrial,
        private readonly ExtendTrial $extendTrial,
        private readonly ConvertTrial $convertTrial,
        private readonly CorrectUsage $correctUsage,
        private readonly RequestPlanChange $requestPlanChange,
        private readonly ApprovePlanChange $approvePlanChange,
        private readonly RejectPlanChange $rejectPlanChange,
        private readonly ApplyApprovedPlanChange $applyApprovedPlanChange,
    ) {}

    public function for(Model $subject): PlanContext
    {
        return app(PlanContext::class, ['subject' => $subject]);
    }

    public function preview(array $input): PreviewResult
    {
        return $this->preview->handle($input);
    }

    public function assign(Model $subject, string $planCode, ?int $version = null, array $options = []): SubscriptionAssignment
    {
        return $this->assignPlan->handle($subject, $planCode, $version, $options);
    }

    public function change(Model $subject, string $planCode, ?int $version = null, array $options = []): SubscriptionAssignment
    {
        return $this->changePlan->handle($subject, $planCode, $version, $options);
    }

    public function recalculate(Model $subject): EntitlementResult
    {
        return $this->recalculate->handle($subject);
    }

    public function startTrial(Model $subject, string $planCode, string $trialType, array $options = []): SubscriptionAssignment
    {
        return $this->startTrial->handle($subject, $planCode, $trialType, $options);
    }

    public function endTrial(Model $subject, string $reason = 'manual_end'): SubscriptionAssignment
    {
        return $this->endTrial->handle($subject, $reason);
    }

    public function extendTrial(Model $subject, int $days): SubscriptionAssignment
    {
        return $this->extendTrial->handle($subject, $days);
    }

    public function convertTrial(Model $subject, array $options = []): SubscriptionAssignment
    {
        return $this->convertTrial->handle($subject, $options);
    }

    public function correctUsage(Model $subject, string $meterCode, float|int $quantity, string $reason, array $options = []): UsageCorrection
    {
        return $this->correctUsage->handle($subject, $meterCode, $quantity, $reason, $options);
    }

    public function requestChange(Model $subject, string $planCode, ?int $version = null, array $options = []): PlanChangeRequest
    {
        return $this->requestPlanChange->handle($subject, $planCode, $version, $options);
    }

    public function approveChange(PlanChangeRequest $request, ?Model $reviewer = null, ?string $note = null): PlanChangeRequest
    {
        return $this->approvePlanChange->handle($request, $reviewer, $note);
    }

    public function rejectChange(PlanChangeRequest $request, ?Model $reviewer = null, ?string $note = null): PlanChangeRequest
    {
        return $this->rejectPlanChange->handle($request, $reviewer, $note);
    }

    public function applyApprovedChange(PlanChangeRequest $request, array $options = []): SubscriptionAssignment
    {
        return $this->applyApprovedPlanChange->handle($request, $options);
    }
}
