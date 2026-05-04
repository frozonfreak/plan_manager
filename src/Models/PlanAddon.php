<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Models;

use FrozonFreak\PlanManager\Models\Concerns\UsesPlanManagerTable;
use FrozonFreak\PlanManager\Support\EntitlementCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PlanAddon extends Model
{
    use UsesPlanManagerTable;

    protected string $planManagerTable = 'plan_addons';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_available' => 'boolean', 'metadata' => 'array'];
    }

    public function planVersion(): BelongsTo
    {
        return $this->belongsTo(PlanVersion::class);
    }

    public function addon(): BelongsTo
    {
        return $this->belongsTo(Addon::class);
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
