<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager;

use FrozonFreak\PlanManager\Actions\ApplyApprovedPlanChange;
use FrozonFreak\PlanManager\Actions\ApprovePlanChange;
use FrozonFreak\PlanManager\Actions\AssignPlan;
use FrozonFreak\PlanManager\Actions\ChangePlan;
use FrozonFreak\PlanManager\Actions\ConsumeUsage;
use FrozonFreak\PlanManager\Actions\ConvertTrial;
use FrozonFreak\PlanManager\Actions\CorrectUsage;
use FrozonFreak\PlanManager\Actions\EndTrial;
use FrozonFreak\PlanManager\Actions\EvaluateRules;
use FrozonFreak\PlanManager\Actions\ExpireTrials;
use FrozonFreak\PlanManager\Actions\ExtendTrial;
use FrozonFreak\PlanManager\Actions\GetUsage;
use FrozonFreak\PlanManager\Actions\PreviewPlanChange;
use FrozonFreak\PlanManager\Actions\RecalculateEntitlementSnapshot;
use FrozonFreak\PlanManager\Actions\RejectPlanChange;
use FrozonFreak\PlanManager\Actions\RequestPlanChange;
use FrozonFreak\PlanManager\Actions\ResolveEntitlements;
use FrozonFreak\PlanManager\Actions\StartTrial;
use FrozonFreak\PlanManager\Console\ExpireTrialsCommand;
use FrozonFreak\PlanManager\Console\InstallCommand;
use FrozonFreak\PlanManager\Console\RecalculateEntitlementsCommand;
use FrozonFreak\PlanManager\Console\ResetUsageCommand;
use FrozonFreak\PlanManager\Contracts\BillingAdapter;
use FrozonFreak\PlanManager\Contracts\RuleActionApplier;
use FrozonFreak\PlanManager\Contracts\RuleConditionEvaluator;
use FrozonFreak\PlanManager\Http\Middleware\EnsurePlanCan;
use FrozonFreak\PlanManager\Http\Middleware\EnsureWithinPlanLimit;
use FrozonFreak\PlanManager\Rules\ActionApplier;
use FrozonFreak\PlanManager\Rules\ConditionEvaluator;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

final class PlanManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/plan-manager.php', 'plan-manager');

        $this->app->singleton(RuleConditionEvaluator::class, ConditionEvaluator::class);
        $this->app->singleton(RuleActionApplier::class, ActionApplier::class);

        $this->app->bind(BillingAdapter::class, function (Container $app): BillingAdapter {
            $name = (string) config('plan-manager.billing.adapter', 'null');
            $adapters = (array) config('plan-manager.billing.adapters', []);
            $class = $adapters[$name] ?? $adapters['null'];

            return $app->make($class);
        });

        foreach ([
            ResolveEntitlements::class,
            ConsumeUsage::class,
            GetUsage::class,
            PreviewPlanChange::class,
            EvaluateRules::class,
            AssignPlan::class,
            ChangePlan::class,
            StartTrial::class,
            EndTrial::class,
            ExtendTrial::class,
            ConvertTrial::class,
            ExpireTrials::class,
            RecalculateEntitlementSnapshot::class,
            CorrectUsage::class,
            RequestPlanChange::class,
            ApprovePlanChange::class,
            RejectPlanChange::class,
            ApplyApprovedPlanChange::class,
        ] as $action) {
            $this->app->bind($action);
        }

        $this->app->singleton(PlanManager::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/plan-manager.php' => config_path('plan-manager.php'),
        ], 'plan-manager-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'plan-manager-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/plan-manager'),
        ], 'plan-manager-views');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'plan-manager');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                RecalculateEntitlementsCommand::class,
                ResetUsageCommand::class,
                ExpireTrialsCommand::class,
            ]);
        }

        $router = $this->app['router'];
        $router->aliasMiddleware((string) config('plan-manager.middleware.plan_can', 'plan.can'), EnsurePlanCan::class);
        $router->aliasMiddleware((string) config('plan-manager.middleware.plan_limit', 'plan.limit'), EnsureWithinPlanLimit::class);

        Blade::if('planCan', fn (string $featureCode): bool => auth()->check() && app(PlanManager::class)->for(auth()->user())->can($featureCode));
        Blade::if('planCannot', fn (string $featureCode): bool => ! auth()->check() || ! app(PlanManager::class)->for(auth()->user())->can($featureCode));
        Blade::directive('planCan', fn (string $expression): string => "<?php if (auth()->check() && app(\\FrozonFreak\\PlanManager\\PlanManager::class)->for(auth()->user())->can({$expression})): ?>");
        Blade::directive('endPlanCan', fn (): string => '<?php endif; ?>');
        Blade::directive('planCannot', fn (string $expression): string => "<?php if (! auth()->check() || ! app(\\FrozonFreak\\PlanManager\\PlanManager::class)->for(auth()->user())->can({$expression})): ?>");
        Blade::directive('endPlanCannot', fn (): string => '<?php endif; ?>');
        Blade::precompiler(fn (string $value): string => str_replace(
            ['@endPlanCannot', '@endPlanCan'],
            ['<?php endif; ?>', '<?php endif; ?>'],
            $value,
        ));
    }
}
