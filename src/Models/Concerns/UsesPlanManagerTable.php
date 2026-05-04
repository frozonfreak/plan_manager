<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Models\Concerns;

trait UsesPlanManagerTable
{
    public function getTable(): string
    {
        if (isset($this->table)) {
            return $this->table;
        }

        return (string) config('plan-manager.table_prefix', 'plan_manager_').$this->planManagerTable;
    }
}
