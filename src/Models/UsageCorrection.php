<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Models;

use FrozonFreak\PlanManager\Enums\UsageCorrectionType;
use FrozonFreak\PlanManager\Models\Concerns\UsesPlanManagerTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class UsageCorrection extends Model
{
    use UsesPlanManagerTable;

    protected string $planManagerTable = 'usage_corrections';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'correction_type' => UsageCorrectionType::class,
            'quantity' => 'decimal:6',
            'metadata' => 'array',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    public function meter(): BelongsTo
    {
        return $this->belongsTo(UsageMeter::class, 'usage_meter_id');
    }

    public function originalUsageRecord(): BelongsTo
    {
        return $this->belongsTo(UsageRecord::class, 'usage_record_id');
    }

    public function resultingUsageRecord(): BelongsTo
    {
        return $this->belongsTo(UsageRecord::class, 'resulting_usage_record_id');
    }
}
