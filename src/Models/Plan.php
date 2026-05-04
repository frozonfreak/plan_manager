<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Models;

use FrozonFreak\PlanManager\Enums\PlanStatus;
use FrozonFreak\PlanManager\Models\Concerns\UsesPlanManagerTable;
use FrozonFreak\PlanManager\Support\EntitlementCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Plan extends Model
{
    use SoftDeletes;
    use UsesPlanManagerTable;

    protected string $planManagerTable = 'plans';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['status' => PlanStatus::class, 'metadata' => 'array'];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PlanVersion::class);
    }

    public function activeVersion(?int $version = null, ?string $billingCycle = null): ?PlanVersion
    {
        return $this->versions()
            ->active()
            ->when($version !== null, fn (Builder $query) => $query->where('version', $version))
            ->when($billingCycle !== null, fn (Builder $query) => $query->where('billing_cycle', $billingCycle))
            ->orderByDesc('version')
            ->first();
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
