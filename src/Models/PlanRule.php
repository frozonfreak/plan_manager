<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Models;

use FrozonFreak\PlanManager\Enums\RuleStatus;
use FrozonFreak\PlanManager\Enums\RuleType;
use FrozonFreak\PlanManager\Enums\StackingPolicy;
use FrozonFreak\PlanManager\Models\Concerns\UsesPlanManagerTable;
use FrozonFreak\PlanManager\Support\EntitlementCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class PlanRule extends Model
{
    use SoftDeletes;
    use UsesPlanManagerTable;

    protected string $planManagerTable = 'plan_rules';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'rule_type' => RuleType::class,
            'conditions_json' => 'array',
            'actions_json' => 'array',
            'stacking_policy' => StackingPolicy::class,
            'status' => RuleStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', RuleStatus::Active)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', RuleStatus::Draft);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', RuleStatus::Archived);
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
