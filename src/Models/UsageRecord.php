<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Models;

use FrozonFreak\PlanManager\Models\Concerns\UsesPlanManagerTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class UsageRecord extends Model
{
    use UsesPlanManagerTable;

    protected string $planManagerTable = 'usage_records';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:6', 'period_start' => 'datetime', 'period_end' => 'datetime', 'metadata' => 'array'];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function meter(): BelongsTo
    {
        return $this->belongsTo(UsageMeter::class, 'usage_meter_id');
    }
}
