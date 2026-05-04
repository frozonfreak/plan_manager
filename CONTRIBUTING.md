# Contributing

Thanks for helping improve Laravel Plan Manager.

## Ground Rules

- Keep the package focused on plan management, entitlement resolution, usage tracking, trials, and adapter boundaries.
- Do not add payment collection, invoices, tax, refunds, settlement, accounting ledgers, or checkout flows.
- Keep subjects polymorphic. Do not assume the billable subject is always `User`.
- Keep billing-provider integrations optional. Do not add hard dependencies on Cashier, Stripe, Razorpay, Paddle, or Chargebee.

## Local Setup

```bash
git clone https://github.com/FrozonFreak/laravel-plan-manager.git
cd laravel-plan-manager
composer install
composer test
```

## Code Style

Run Pint before opening a pull request:

```bash
composer format
```

To check formatting without changing files:

```bash
composer format:test
```

## Tests

Add or update Pest tests for behavior changes:

```bash
composer test
```

Use focused tests while developing:

```bash
vendor/bin/pest tests/Unit
vendor/bin/pest tests/Feature
```

## Pull Requests

Please include:

- A clear description of the problem and solution.
- Tests for new behavior.
- Documentation updates when public API changes.
- Notes about backwards compatibility or migration impact.

Small, focused pull requests are easier to review and merge.
