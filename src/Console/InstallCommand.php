<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Console;

use Illuminate\Console\Command;

final class InstallCommand extends Command
{
    protected $signature = 'plan-manager:install';

    protected $description = 'Publish Plan Manager config and migrations.';

    public function handle(): int
    {
        $this->call('vendor:publish', ['--tag' => 'plan-manager-config']);
        $this->call('vendor:publish', ['--tag' => 'plan-manager-migrations']);
        $this->call('vendor:publish', ['--tag' => 'plan-manager-views']);

        if ($this->confirm('Run migrations now?', false)) {
            $this->call('migrate');
        }

        if ($this->confirm('Seed demo plans?', false)) {
            $this->call('db:seed', ['--class' => 'FrozonFreak\\PlanManager\\Database\\Seeders\\PlanManagerDemoSeeder']);
        }

        $this->info('Plan Manager installed.');

        return self::SUCCESS;
    }
}
