<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Models;

use FrozonFreak\PlanManager\Enums\SubscriptionStatus;
use FrozonFreak\PlanManager\Enums\TrialType;
use FrozonFreak\PlanManager\Models\Concerns\UsesPlanManagerTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class SubscriptionAssignment extends Model
{
    use SoftDeletes;
    use UsesPlanManagerTable;

    protected string $planManagerTable = 'subscription_assignments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'trial_type' => TrialType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'trial_started_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'trial_usage_limits' => 'array',
            'trial_usage_consumed' => 'array',
            'trial_ended_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function planVersion(): BelongsTo
    {
        return $this->belongsTo(PlanVersion::class);
    }

    public function scopeCurrentFor(Builder $query, Model $subject): Builder
    {
        return $query->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', (string) $subject->getKey())
            ->whereIn('status', [SubscriptionStatus::Trialing->value, SubscriptionStatus::Active->value]);
    }

    public function scopeTrialing(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatus::Trialing);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatus::Active);
    }

    public function scopePaused(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatus::Paused);
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatus::Cancelled);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatus::Expired);
    }
}
