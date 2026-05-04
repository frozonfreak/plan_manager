<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Models;

use FrozonFreak\PlanManager\Models\Concerns\UsesPlanManagerTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class EntitlementSnapshot extends Model
{
    use UsesPlanManagerTable;

    protected string $planManagerTable = 'entitlement_snapshots';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['entitlements' => 'array', 'usage_summary' => 'array', 'trial' => 'array', 'addons' => 'array', 'resolved_at' => 'datetime', 'metadata' => 'array'];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(SubscriptionAssignment::class, 'subscription_assignment_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function planVersion(): BelongsTo
    {
        return $this->belongsTo(PlanVersion::class);
    }
}
