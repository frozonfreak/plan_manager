<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Models;

use FrozonFreak\PlanManager\Enums\UsageResetPeriod;
use FrozonFreak\PlanManager\Models\Concerns\UsesPlanManagerTable;
use FrozonFreak\PlanManager\Support\EntitlementCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class UsageMeter extends Model
{
    use SoftDeletes;
    use UsesPlanManagerTable;

    protected string $planManagerTable = 'usage_meters';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['reset_period' => UsageResetPeriod::class, 'metadata' => 'array'];
    }

    public function records(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
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
