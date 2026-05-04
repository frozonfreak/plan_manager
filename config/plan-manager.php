<?php

declare(strict_types=1);

use FrozonFreak\PlanManager\Billing\CashierStripeAdapter;
use FrozonFreak\PlanManager\Billing\ChargebeeAdapter;
use FrozonFreak\PlanManager\Billing\ManualBillingAdapter;
use FrozonFreak\PlanManager\Billing\NullBillingAdapter;
use FrozonFreak\PlanManager\Billing\PaddleAdapter;
use FrozonFreak\PlanManager\Billing\RazorpayAdapter;

return [
    'table_prefix' => 'plan_manager_',

    'subject_model' => null,
    'subject_resolver' => null,

    'cache' => [
        'enabled' => true,
        'ttl_seconds' => 300,
        'store' => null,
    ],

    'billing' => [
        'adapter' => 'null',
        'adapters' => [
            'null' => NullBillingAdapter::class,
            'manual' => ManualBillingAdapter::class,
            'cashier_stripe' => CashierStripeAdapter::class,
            'razorpay' => RazorpayAdapter::class,
            'paddle' => PaddleAdapter::class,
            'chargebee' => ChargebeeAdapter::class,
        ],
    ],

    'admin' => [
        'enabled' => false,
        'route_prefix' => 'plan-manager',
        'route_name_prefix' => 'plan-manager.',
        'middleware' => ['web', 'auth'],
    ],

    'usage' => [
        'strict_limits' => true,
        'allow_negative_remaining' => false,
    ],

    'trials' => [
        'enabled' => true,
        'allow_multiple_trials_per_subject' => false,
        'allow_trial_after_cancellation' => false,
        'expire_on_first_usage_limit_reached' => true,
        'allow_exact_limit_consumption' => true,
        'auto_expire_trials_during_resolution' => true,
    ],

    'features' => [
        // Host apps may document known feature codes/types here.
    ],

    'middleware' => [
        'plan_can' => 'plan.can',
        'plan_limit' => 'plan.limit',
    ],
];
