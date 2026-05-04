<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Models;

use FrozonFreak\PlanManager\Enums\FeatureType;
use FrozonFreak\PlanManager\Models\Concerns\UsesPlanManagerTable;
use FrozonFreak\PlanManager\Support\EntitlementCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Feature extends Model
{
    use SoftDeletes;
    use UsesPlanManagerTable;

    protected string $planManagerTable = 'features';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['type' => FeatureType::class, 'default_value' => 'array', 'metadata' => 'array'];
    }

    public function values(): HasMany
    {
        return $this->hasMany(PlanFeatureValue::class);
    }

    public function castValue(mixed $value): mixed
    {
        $value = is_array($value) && array_key_exists('value', $value) && count($value) === 1 ? $value['value'] : $value;

        return match ($this->type) {
            FeatureType::Boolean => (bool) $value,
            FeatureType::Integer => (int) $value,
            FeatureType::Decimal => (float) $value,
            FeatureType::String => $value === null ? null : (string) $value,
            FeatureType::Json => $value,
        };
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query;
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query;
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->onlyTrashed();
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
