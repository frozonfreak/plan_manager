<?php

declare(strict_types=1);

use FrozonFreak\PlanManager\Enums\SubscriptionStatus;
use FrozonFreak\PlanManager\Facades\PlanManager;
use FrozonFreak\PlanManager\Models\SubscriptionAssignment;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

it('assigns and changes plans for polymorphic subjects', function (): void {
    $subject = $this->subject();
    $assignment = PlanManager::assign($subject, 'free');

    expect($assignment->subject_type)->toBe($subject->getMorphClass())
        ->and(PlanManager::for($subject)->limit('users.max'))->toBe(1);

    PlanManager::change($subject, 'pro');

    expect(PlanManager::for($subject)->can('export.enabled'))->toBeTrue()
        ->and(PlanManager::for($subject)->limit('users.max'))->toBe(3);
});

it('resolves boolean features numeric limits and consumes usage', function (): void {
    $subject = $this->subject();
    PlanManager::assign($subject, 'pro');
    PlanManager::for($subject)->consume('api_calls', 12);

    expect(PlanManager::for($subject)->can('api.enabled'))->toBeTrue()
        ->and(PlanManager::for($subject)->limit('users.max'))->toBe(3)
        ->and(PlanManager::for($subject)->usage('api_calls')->used())->toBe(12.0)
        ->and(PlanManager::for($subject)->remaining('api_calls'))->toBe(9988.0);
});

it('middleware blocks missing entitlement and allows valid entitlement', function (): void {
    $subject = $this->subject();
    PlanManager::assign($subject, 'free');

    $this->actingAs($subject);
    Route::middleware('plan.can:export.enabled')->get('/blocked', fn () => 'ok');
    Route::middleware('plan.can:api.enabled')->get('/allowed', fn () => 'ok');

    $this->get('/blocked')->assertForbidden();

    PlanManager::change($subject, 'pro');
    $this->get('/allowed')->assertOk()->assertSee('ok');
});

it('blade directive renders correctly', function (): void {
    $subject = $this->subject();
    PlanManager::assign($subject, 'pro');
    $this->actingAs($subject);

    expect(Blade::render("@planCan('export.enabled')\nyes\n@endPlanCan", deleteCachedView: true))->toBe("yes\n")
        ->and(Blade::render("@planCannot('export.enabled')\nno\n@endPlanCannot", deleteCachedView: true))->toBe('');
});

it('handles time limited trial expiry during entitlement resolution', function (): void {
    $this->travelTo('2026-05-04 10:00:00');
    $subject = $this->subject();
    PlanManager::startTrial($subject, 'pro', 'time_limited', ['days' => 14]);
    $this->travelTo('2026-05-18 10:00:00');

    $result = PlanManager::for($subject)->entitlements(true);

    expect($result->trial->isExpired())->toBeTrue()
        ->and(SubscriptionAssignment::query()->latest('id')->first()->status)->toBe(SubscriptionStatus::Expired);
});

it('converts and extends trials', function (): void {
    $this->travelTo('2026-05-04 10:00:00');
    $subject = $this->subject();
    PlanManager::startTrial($subject, 'pro', 'time_limited', ['days' => 14]);
    PlanManager::extendTrial($subject, 7);

    expect(PlanManager::for($subject)->trialRemainingDays())->toBe(21);

    PlanManager::convertTrial($subject);

    expect(PlanManager::for($subject)->assignment()->status)->toBe(SubscriptionStatus::Active);
});

it('expires hybrid trial on usage before time', function (): void {
    $this->travelTo('2026-05-04 10:00:00');
    $subject = $this->subject();
    PlanManager::startTrial($subject, 'business', 'time_and_usage_limited', ['days' => 14, 'usage_limits' => ['api_calls' => 1000]]);
    PlanManager::for($subject)->consume('api_calls', 1000);

    expect(SubscriptionAssignment::query()->latest('id')->first()->status)->toBe(SubscriptionStatus::Expired)
        ->and(SubscriptionAssignment::query()->latest('id')->first()->trial_end_reason)->toBe('usage_exhausted');
});

it('expires old trials with the command', function (): void {
    $this->travelTo('2026-05-04 10:00:00');
    $subject = $this->subject();
    PlanManager::startTrial($subject, 'pro', 'time_limited', ['days' => 1]);
    $this->travelTo('2026-05-05 10:00:00');

    $this->artisan('plan-manager:expire-trials')->assertExitCode(0);

    expect(SubscriptionAssignment::query()->latest('id')->first()->status)->toBe(SubscriptionStatus::Expired);
});

it('registers opt in admin routes', function (): void {
    $this->actingAs($this->subject());

    $this->get('/plan-manager')->assertOk()->assertSee('Dashboard');
});
