# Laravel Plan Manager

[![Tests](https://img.shields.io/github/actions/workflow/status/frozonfreak/plan_manager/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/frozonfreak/plan_manager/actions/workflows/tests.yml)
[![License](https://img.shields.io/github/license/frozonfreak/plan_manager.svg?style=flat-square)](LICENSE)

`frozonfreak/laravel-plan-manager` is a Laravel package for centralizing SaaS plan rules, features, limits, usage meters, add-ons, previews, and local trial entitlement logic.

It is **not** a billing platform. It does not charge customers, create invoices, calculate tax, issue refunds, manage settlement, or replace Stripe, Razorpay, Paddle, Chargebee, or Laravel Cashier. Billing providers remain responsible for money movement. This package answers one question: **what is this subject entitled to use right now?**

Trial support is local plan-management logic. Billing providers may also have trial settings, but this package does not rely on billing-provider trials to resolve access.

## Installation

Install with Composer:

```bash
composer require frozonfreak/laravel-plan-manager
```

Publish config and migrations:

```bash
php artisan vendor:publish --tag=plan-manager-config
php artisan vendor:publish --tag=plan-manager-migrations
php artisan vendor:publish --tag=plan-manager-views
php artisan migrate
```

Or use the installer:

```bash
php artisan plan-manager:install
```

The service provider is auto-discovered through Composer.

## Basic Usage

```php
use FrozonFreak\PlanManager\Facades\PlanManager;

PlanManager::assign($team, 'pro');

PlanManager::for($team)->can('export.enabled');
PlanManager::for($team)->limit('users.max');
PlanManager::for($team)->consume('api_calls');
PlanManager::for($team)->remaining('api_calls');
```

Subjects are polymorphic. They can be users, teams, tenants, companies, farms, or any Eloquent model.

## Creating Plans

The demo seeder creates `free`, `pro`, `business`, and `enterprise` plans:

```bash
php artisan db:seed --class="FrozonFreak\PlanManager\Database\Seeders\PlanManagerDemoSeeder"
```

Plan versions are mandatory. Entitlements resolve against a specific version so existing customers can remain on historical terms.

Use `-1` for unlimited numeric limits:

```php
'users.max' => -1
'api_calls.monthly' => -1
```

## Entitlements

```php
$context = PlanManager::for($team);

$context->plan();
$context->planVersion();
$context->assignment();
$context->entitlements();

$context->ensureCan('export.enabled');
$context->ensureWithinLimit('users.max', $newUserCount);
```

## Usage

```php
PlanManager::for($team)->consume('api_calls', 1);

$usage = PlanManager::for($team)->usage('api_calls');

$usage->used();
$usage->limit();
$usage->remaining();
$usage->isUnlimited();
$usage->exceeded();
```

Usage records are append-only by default and reset by period (`never`, `daily`, `monthly`, `yearly`) without mutating old records.

## Add-ons

```php
PlanManager::for($team)->allowsAddon('advanced_reports');
```

Add-ons are entitlement modifiers only. Payment collection for add-ons belongs to your billing provider.

## Usage Corrections

Usage records remain append-only. Corrections create a ledger row and a compensating usage record:

```php
PlanManager::correctUsage($team, 'api_calls', -25, 'Duplicate import adjustment');

PlanManager::correctUsage($team, 'api_calls', 1000, 'Admin reset to audited total', [
    'type' => 'set_to',
]);
```

Use corrections for operational fixes. This is not an accounting ledger.

## Trials

Trials are local entitlement rules:

```php
PlanManager::startTrial($team, 'pro', 'time_limited', [
    'days' => 14,
]);

PlanManager::startTrial($team, 'business', 'usage_limited', [
    'usage_limits' => [
        'api_calls' => 1000,
    ],
]);

PlanManager::startTrial($team, 'business', 'time_and_usage_limited', [
    'days' => 14,
    'usage_limits' => [
        'api_calls' => 1000,
    ],
]);
```

Check trial state:

```php
PlanManager::for($team)->isTrialing();
PlanManager::for($team)->trialRemainingDays();
PlanManager::for($team)->trialUsageRemaining('api_calls');
PlanManager::for($team)->ensureTrialActive();
```

End or convert trials:

```php
PlanManager::endTrial($team);
PlanManager::extendTrial($team, 7);
PlanManager::convertTrial($team);
```

Usage-limited trials are enforced before inserting usage. If a trial includes 100 API calls, the 100th call is allowed by default and the 101st is blocked.

Expire trials on a schedule:

```bash
php artisan plan-manager:expire-trials
```

Entitlement resolution can also auto-expire old trials when `trials.auto_expire_trials_during_resolution` is enabled.

## Preview

```php
$preview = PlanManager::preview([
    'subject' => $team,
    'plan' => 'business',
    'billing_cycle' => 'yearly',
    'addons' => ['advanced_reports'],
    'usage' => [
        'api_calls' => 12400,
    ],
    'trial' => [
        'type' => 'time_and_usage_limited',
        'days' => 14,
        'usage_limits' => [
            'api_calls' => 1000,
        ],
    ],
]);

$preview->toArray();
```

Preview returns features, add-ons, usage, trial availability, display pricing, applied rules, warnings, and blocks.

## Approval Workflows

Plan changes can be requested, reviewed, and then applied:

```php
$request = PlanManager::requestChange($team, 'business', null, [
    'reason' => 'Team needs higher API limits',
]);

PlanManager::approveChange($request, $admin, 'Approved for launch week');
PlanManager::applyApprovedChange($request);
```

Rejected requests do not change entitlements:

```php
PlanManager::rejectChange($request, $admin, 'Usage does not justify upgrade');
```

## Middleware

```php
Route::middleware('plan.can:export.enabled')->get('/reports/export', ExportController::class);
Route::middleware('plan.limit:users.max,5')->post('/users', StoreUserController::class);
```

By default the subject is the authenticated user. Configure `subject_resolver` to resolve a team, tenant, or other model.

## Validation

```php
use FrozonFreak\PlanManager\Validation\WithinPlanLimit;

'users_count' => [new WithinPlanLimit($team, 'users.max')]
```

## Blade

```blade
@planCan('export.enabled')
    ...
@endPlanCan

@planCannot('export.enabled')
    ...
@endPlanCannot
```

## Billing Adapter Boundary

The default adapter is `null` and performs no external billing work. `manual` is available for offline/manual billing. Both fully support local trials and entitlement resolution.

Cashier support is optional:

```php
'billing' => [
    'adapter' => 'cashier_stripe',
]
```

There is no hard dependency on Cashier. If `cashier_stripe` is selected without Cashier installed, the adapter throws a clear `BillingAdapterException`.

Additional guarded adapter boundaries are available for `razorpay`, `paddle`, and `chargebee`. They are intentionally metadata/sync boundaries only and throw a clear exception if the relevant SDK/client is not installed.

Cashier already handles subscription billing, coupons, swapping, quantities, grace periods, and invoices. This package intentionally does not duplicate that work.

## Admin UI

The admin UI is optional and disabled by default:

```php
'admin' => [
    'enabled' => true,
    'route_prefix' => 'plan-manager',
    'middleware' => ['web', 'auth'],
],
```

It includes:

- Plan listing/detail/basic editing
- Rule listing/create/edit
- A simple form-based rule JSON builder
- Plan change approval queue
- Usage correction ledger entry form

Publish the views if you want to customize the screens:

```bash
php artisan vendor:publish --tag=plan-manager-views
```

## Commands

```bash
php artisan plan-manager:install
php artisan plan-manager:recalculate-entitlements --subject-type="App\Models\Team" --subject-id=1
php artisan plan-manager:recalculate-entitlements --all
php artisan plan-manager:reset-usage
php artisan plan-manager:expire-trials --dry-run
```

## Testing

```bash
composer install
composer test
```

The test suite uses Pest and Orchestra Testbench.

## Code Style

This package uses Laravel Pint:

```bash
composer format
composer format:test
```

## Contributing

Contributions are welcome. Please read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a pull request.

For security issues, do not open a public issue. Follow [SECURITY.md](SECURITY.md).

## License

Laravel Plan Manager is open-source software licensed under the [MIT license](LICENSE).

## Roadmap

- More polished admin UX and authorization policy hooks
- Provider-specific subscription metadata sync helpers
- Usage correction review/approval policies
- Drag-and-drop visual rule builder

Out of scope: tax/GST, invoices, payment checkout, refunds, revenue recognition, and accounting ledgers.

Deferred for now: multi-currency billing reconciliation.
