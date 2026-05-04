<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Models;

use FrozonFreak\PlanManager\Enums\PlanChangeRequestStatus;
use FrozonFreak\PlanManager\Models\Concerns\UsesPlanManagerTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class PlanChangeRequest extends Model
{
    use UsesPlanManagerTable;

    protected string $planManagerTable = 'plan_change_requests';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => PlanChangeRequestStatus::class,
            'reviewed_at' => 'datetime',
            'applied_at' => 'datetime',
            'preview' => 'array',
            'metadata' => 'array',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function requestedBy(): MorphTo
    {
        return $this->morphTo('requested_by');
    }

    public function reviewedBy(): MorphTo
    {
        return $this->morphTo('reviewed_by');
    }

    public function currentPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'current_plan_id');
    }

    public function currentPlanVersion(): BelongsTo
    {
        return $this->belongsTo(PlanVersion::class, 'current_plan_version_id');
    }

    public function targetPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'target_plan_id');
    }

    public function targetPlanVersion(): BelongsTo
    {
        return $this->belongsTo(PlanVersion::class, 'target_plan_version_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PlanChangeRequestStatus::Pending);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', PlanChangeRequestStatus::Approved);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', PlanChangeRequestStatus::Rejected);
    }

    public function scopeApplied(Builder $query): Builder
    {
        return $query->where('status', PlanChangeRequestStatus::Applied);
    }
}
