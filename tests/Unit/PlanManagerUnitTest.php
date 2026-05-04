<?php

declare(strict_types=1);

use FrozonFreak\PlanManager\Actions\GetUsage;
use FrozonFreak\PlanManager\Billing\CashierStripeAdapter;
use FrozonFreak\PlanManager\Billing\RazorpayAdapter;
use FrozonFreak\PlanManager\Data\RuleEvaluationContext;
use FrozonFreak\PlanManager\Data\TrialResult;
use FrozonFreak\PlanManager\Enums\PlanChangeRequestStatus;
use FrozonFreak\PlanManager\Enums\UsageResetPeriod;
use FrozonFreak\PlanManager\Exceptions\BillingAdapterException;
use FrozonFreak\PlanManager\Exceptions\TrialUsageLimitExceededException;
use FrozonFreak\PlanManager\Exceptions\UsageLimitExceededException;
use FrozonFreak\PlanManager\Facades\PlanManager;
use FrozonFreak\PlanManager\Models\Feature;
use FrozonFreak\PlanManager\Models\UsageMeter;
use FrozonFreak\PlanManager\Rules\ActionApplier;
use FrozonFreak\PlanManager\Rules\ConditionEvaluator;
use Illuminate\Support\Facades\Config;

it('casts plan feature values by feature type', function (): void {
    expect(Feature::query()->where('code', 'export.enabled')->first()->castValue(['value' => 1]))->toBeTrue()
        ->and(Feature::query()->where('code', 'users.max')->first()->castValue(['value' => '5']))->toBe(5)
        ->and(Feature::query()->where('code', 'support.level')->first()->castValue(['value' => 'email']))->toBe('email');
});

it('calculates usage periods', function (): void {
    $this->travelTo('2026-05-04 10:00:00');
    $meter = UsageMeter::query()->where('code', 'api_calls')->first();
    [$start, $end] = GetUsage::periodFor($meter);
    expect($start->toDateString())->toBe('2026-05-01')->and($end->toDateString())->toBe('2026-05-31');

    $meter->forceFill(['reset_period' => UsageResetPeriod::Daily])->save();
    [$start, $end] = GetUsage::periodFor($meter->refresh());
    expect($start->toDateString())->toBe('2026-05-04')->and($end->toDateString())->toBe('2026-05-04');
});

it('resolves entitlements and unlimited usage', function (): void {
    $subject = $this->subject();
    PlanManager::assign($subject, 'enterprise');

    $result = PlanManager::for($subject)->entitlements(true);

    expect($result->features['users.max'])->toBe(-1)
        ->and(PlanManager::for($subject)->usage('api_calls')->isUnlimited())->toBeTrue();
});

it('blocks strict usage over limit', function (): void {
    $subject = $this->subject();
    PlanManager::assign($subject, 'pro');
    PlanManager::for($subject)->consume('api_calls', 10000);

    PlanManager::for($subject)->consume('api_calls', 1);
})->throws(UsageLimitExceededException::class);

it('evaluates nested all any conditions', function (): void {
    $subject = $this->subject();
    PlanManager::assign($subject, 'pro', null, ['billing_cycle' => 'monthly']);
    $assignment = PlanManager::for($subject)->assignment();
    $context = new RuleEvaluationContext(subject: $subject, plan: $assignment->plan, planVersion: $assignment->planVersion, assignment: $assignment, account: ['country' => 'IN']);

    $conditions = ['all' => [
        ['field' => 'plan.code', 'operator' => '=', 'value' => 'pro'],
        ['any' => [
            ['field' => 'account.country', 'operator' => '=', 'value' => 'US'],
            ['field' => 'account.country', 'operator' => '=', 'value' => 'IN'],
        ]],
    ]];

    expect(app(ConditionEvaluator::class)->passes($conditions, $context))->toBeTrue();
});

it('applies rule actions', function (): void {
    $state = app(ActionApplier::class)->apply([
        ['type' => 'set_feature_value', 'feature' => 'users.max', 'value' => 10],
        ['type' => 'enable_addon', 'addon' => 'advanced_reports'],
        ['type' => 'apply_display_discount', 'label' => '20% yearly discount', 'value_type' => 'percentage', 'value' => 20],
    ], new RuleEvaluationContext, ['features' => [], 'addons' => [], 'display_pricing' => ['discounts' => []]]);

    expect($state['features']['users.max'])->toBe(10)
        ->and($state['addons']['advanced_reports'])->toBeTrue()
        ->and($state['display_pricing']['discounts'][0]['value'])->toBe(20);
});

it('generates preview results with warnings and trial availability', function (): void {
    $subject = $this->subject();
    $preview = PlanManager::preview([
        'subject' => $subject,
        'plan' => 'business',
        'addons' => ['advanced_reports'],
        'usage' => ['api_calls' => 124000],
        'trial' => ['type' => 'time_and_usage_limited', 'days' => 14, 'usage_limits' => ['api_calls' => 1000]],
    ]);

    expect($preview->features['users.max'])->toBe(10)
        ->and($preview->addons['advanced_reports'])->toBeTrue()
        ->and($preview->usage['api_calls']['exceeded'])->toBeTrue()
        ->and($preview->trial['available'])->toBeTrue();
});

it('calculates trial result remaining days and usage', function (): void {
    $this->travelTo('2026-05-04 10:00:00');
    $trial = new TrialResult(true, 'time_and_usage_limited', now(), now()->addDays(14), usageLimits: ['api_calls' => 100], usageConsumed: ['api_calls' => 40], usageRemaining: ['api_calls' => 60]);

    expect($trial->remainingDays())->toBe(14)
        ->and($trial->remainingUsage('api_calls'))->toBe(60.0);
});

it('starts usage-limited trials and blocks the 101st use when limit is 100', function (): void {
    $subject = $this->subject();
    PlanManager::startTrial($subject, 'business', 'usage_limited', ['usage_limits' => ['api_calls' => 100]]);
    PlanManager::for($subject)->consume('api_calls', 100);

    PlanManager::for($subject)->consume('api_calls', 1);
})->throws(TrialUsageLimitExceededException::class);

it('blocks repeated trial preview when disallowed', function (): void {
    $subject = $this->subject();
    PlanManager::startTrial($subject, 'pro', 'time_limited', ['days' => 14]);
    Config::set('plan-manager.trials.allow_multiple_trials_per_subject', false);

    $preview = PlanManager::preview(['subject' => $subject, 'plan' => 'pro', 'trial' => ['type' => 'time_limited', 'days' => 14]]);

    expect($preview->trial['available'])->toBeFalse()
        ->and($preview->trial['blocks'])->toContain('Subject has already used trial');
});

it('fails cashier adapter gracefully when cashier is missing', function (): void {
    app(CashierStripeAdapter::class)->syncSubscription($this->subject());
})->throws(BillingAdapterException::class);

it('fails optional provider adapters gracefully when sdk is missing', function (): void {
    app(RazorpayAdapter::class)->syncSubscription($this->subject());
})->throws(BillingAdapterException::class);

it('records usage corrections as append only records', function (): void {
    $subject = $this->subject();
    PlanManager::assign($subject, 'pro');
    PlanManager::for($subject)->consume('api_calls', 10);

    $correction = PlanManager::correctUsage($subject, 'api_calls', -3, 'Duplicate import adjustment');

    expect($correction->reason)->toBe('Duplicate import adjustment')
        ->and(PlanManager::for($subject)->usage('api_calls')->used())->toBe(7.0);
});

it('requests approves and applies a plan change', function (): void {
    $subject = $this->subject();
    PlanManager::assign($subject, 'free');

    $request = PlanManager::requestChange($subject, 'pro', null, ['reason' => 'Needs exports']);
    expect($request->status)->toBe(PlanChangeRequestStatus::Pending);

    PlanManager::approveChange($request);
    PlanManager::applyApprovedChange($request->refresh());

    expect($request->refresh()->status)->toBe(PlanChangeRequestStatus::Applied)
        ->and(PlanManager::for($subject)->can('export.enabled'))->toBeTrue();
});
