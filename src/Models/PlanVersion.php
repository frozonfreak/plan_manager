<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Models;

use FrozonFreak\PlanManager\Enums\PlanVersionStatus;
use FrozonFreak\PlanManager\Models\Concerns\UsesPlanManagerTable;
use FrozonFreak\PlanManager\Support\EntitlementCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class PlanVersion extends Model
{
    use SoftDeletes;
    use UsesPlanManagerTable;

    protected string $planManagerTable = 'plan_versions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => PlanVersionStatus::class,
            'display_price' => 'decimal:6',
            'effective_from' => 'datetime',
            'effective_until' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function featureValues(): HasMany
    {
        return $this->hasMany(PlanFeatureValue::class);
    }

    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(Addon::class, (string) config('plan-manager.table_prefix', 'plan_manager_').'plan_addons')
            ->withPivot(['is_available', 'metadata'])
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', PlanVersionStatus::Active);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', PlanVersionStatus::Draft);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', PlanVersionStatus::Archived);
    }

    protected static function booted(): void
    {
        self::saved(function (): void {
            EntitlementCache::bumpVersion();
        });
        self::deleted(function (): void {
            EntitlementCache::bumpVersion();
        });
    }
}
