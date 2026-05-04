<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Actions;

use FrozonFreak\PlanManager\Enums\PlanChangeRequestStatus;
use FrozonFreak\PlanManager\Exceptions\PlanChangeRequestException;
use FrozonFreak\PlanManager\Models\PlanAuditLog;
use FrozonFreak\PlanManager\Models\PlanChangeRequest;
use Illuminate\Database\Eloquent\Model;

final class RejectPlanChange
{
    public function handle(PlanChangeRequest $request, ?Model $reviewer = null, ?string $note = null): PlanChangeRequest
    {
        if ($request->status !== PlanChangeRequestStatus::Pending) {
            throw new PlanChangeRequestException('Only pending plan change requests can be rejected.');
        }

        $request->forceFill([
            'status' => PlanChangeRequestStatus::Rejected,
            'reviewed_by_type' => $reviewer?->getMorphClass(),
            'reviewed_by_id' => $reviewer ? (string) $reviewer->getKey() : null,
            'reviewed_at' => now(),
            'review_note' => $note,
        ])->save();

        PlanAuditLog::query()->create([
            'subject_type' => $request->subject_type,
            'subject_id' => $request->subject_id,
            'actor_type' => $reviewer?->getMorphClass(),
            'actor_id' => $reviewer ? (string) $reviewer->getKey() : null,
            'event' => 'plan_change.rejected',
            'new_values' => ['request_id' => $request->id, 'note' => $note],
        ]);

        return $request->refresh();
    }
}
