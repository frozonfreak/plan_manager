<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Console;

use Illuminate\Console\Command;

final class ResetUsageCommand extends Command
{
    protected $signature = 'plan-manager:reset-usage';

    protected $description = 'Explain usage reset behavior.';

    public function handle(): int
    {
        $this->info('Usage is append-only and period-based. New daily/monthly/yearly periods automatically start fresh without mutating old records.');

        return self::SUCCESS;
    }
}
