<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Models;

use FrozonFreak\PlanManager\Enums\AddonType;
use FrozonFreak\PlanManager\Enums\PlanStatus;
use FrozonFreak\PlanManager\Models\Concerns\UsesPlanManagerTable;
use FrozonFreak\PlanManager\Support\EntitlementCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Addon extends Model
{
    use SoftDeletes;
    use UsesPlanManagerTable;

    protected string $planManagerTable = 'addons';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['type' => AddonType::class, 'status' => PlanStatus::class, 'display_price' => 'decimal:6', 'metadata' => 'array'];
    }

    public function planVersions(): BelongsToMany
    {
        return $this->belongsToMany(PlanVersion::class, (string) config('plan-manager.table_prefix', 'plan_manager_').'plan_addons')
            ->withPivot(['is_available', 'metadata'])
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', PlanStatus::Active);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', PlanStatus::Draft);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', PlanStatus::Archived);
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
