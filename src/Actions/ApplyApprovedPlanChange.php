<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Actions;

use FrozonFreak\PlanManager\Enums\PlanChangeRequestStatus;
use FrozonFreak\PlanManager\Exceptions\PlanChangeRequestException;
use FrozonFreak\PlanManager\Models\PlanAuditLog;
use FrozonFreak\PlanManager\Models\PlanChangeRequest;
use FrozonFreak\PlanManager\Models\SubscriptionAssignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;

final class ApplyApprovedPlanChange
{
    public function __construct(private readonly ChangePlan $changePlan) {}

    public function handle(PlanChangeRequest $request, array $options = []): SubscriptionAssignment
    {
        if ($request->status !== PlanChangeRequestStatus::Approved) {
            throw new PlanChangeRequestException('Only approved plan change requests can be applied.');
        }

        $subject = $this->resolveSubject($request);
        if (! $subject) {
            throw new PlanChangeRequestException('The requested subject could not be resolved.');
        }

        return DB::transaction(function () use ($request, $subject, $options): SubscriptionAssignment {
            $assignment = $this->changePlan->handle($subject, $request->targetPlan->code, $request->targetPlanVersion->version, [
                'billing_cycle' => $request->billing_cycle,
                'metadata' => ['plan_change_request_id' => $request->id] + (array) ($options['metadata'] ?? []),
            ] + $options);

            $request->forceFill([
                'status' => PlanChangeRequestStatus::Applied,
                'applied_at' => now(),
            ])->save();

            PlanAuditLog::query()->create([
                'subject_type' => $request->subject_type,
                'subject_id' => $request->subject_id,
                'event' => 'plan_change.applied',
                'new_values' => ['request_id' => $request->id, 'assignment_id' => $assignment->id],
            ]);

            return $assignment;
        });
    }

    private function resolveSubject(PlanChangeRequest $request): ?Model
    {
        $class = Relation::getMorphedModel($request->subject_type) ?? $request->subject_type;
        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            return null;
        }

        return $class::query()->find($request->subject_id);
    }
}
