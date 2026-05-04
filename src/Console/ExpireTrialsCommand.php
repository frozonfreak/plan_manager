<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Console;

use FrozonFreak\PlanManager\Actions\ExpireTrials;
use Illuminate\Console\Command;

final class ExpireTrialsCommand extends Command
{
    protected $signature = 'plan-manager:expire-trials {--dry-run} {--subject-type=} {--subject-id=}';

    protected $description = 'Expire local trials that have reached their time or usage limit.';

    public function handle(ExpireTrials $expireTrials): int
    {
        $expired = $expireTrials->handle(
            dryRun: (bool) $this->option('dry-run'),
            subjectType: $this->option('subject-type') ? (string) $this->option('subject-type') : null,
            subjectId: $this->option('subject-id') ? (string) $this->option('subject-id') : null,
        );

        $this->info(($this->option('dry-run') ? 'Would expire ' : 'Expired ').$expired->count().' trial(s).');

        return self::SUCCESS;
    }
}
