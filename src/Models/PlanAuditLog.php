<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Models;

use FrozonFreak\PlanManager\Models\Concerns\UsesPlanManagerTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class PlanAuditLog extends Model
{
    use UsesPlanManagerTable;

    public const UPDATED_AT = null;

    protected string $planManagerTable = 'plan_audit_logs';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['old_values' => 'array', 'new_values' => 'array', 'metadata' => 'array', 'created_at' => 'datetime'];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }
}
