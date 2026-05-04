<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Models;

use FrozonFreak\PlanManager\Models\Concerns\UsesPlanManagerTable;
use FrozonFreak\PlanManager\Support\EntitlementCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PlanFeatureValue extends Model
{
    use UsesPlanManagerTable;

    protected string $planManagerTable = 'plan_feature_values';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    public function planVersion(): BelongsTo
    {
        return $this->belongsTo(PlanVersion::class);
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }

    public function castedValue(): mixed
    {
        return $this->feature->castValue($this->value);
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
