<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Tests;

use FrozonFreak\PlanManager\Database\Seeders\PlanManagerDemoSeeder;
use FrozonFreak\PlanManager\PlanManagerServiceProvider;
use FrozonFreak\PlanManager\Tests\Fixtures\TestSubject;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [PlanManagerServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $driver = env('DB_CONNECTION', 'sqlite');

        $app['config']->set('database.default', 'testing');
        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app['config']->set('database.connections.testing', [
            'driver' => $driver,
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', $driver === 'pgsql' ? '5432' : '3306'),
            'database' => env('DB_DATABASE', $driver === 'sqlite' ? ':memory:' : 'plan_manager_test'),
            'username' => env('DB_USERNAME', $driver === 'pgsql' ? 'postgres' : 'root'),
            'password' => env('DB_PASSWORD', $driver === 'pgsql' ? 'postgres' : ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ]);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('plan-manager.admin.enabled', true);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('test_subjects');
        Schema::create('test_subjects', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        $this->seed(PlanManagerDemoSeeder::class);
    }

    protected function subject(): TestSubject
    {
        return TestSubject::query()->create(['name' => 'Acme']);
    }
}
