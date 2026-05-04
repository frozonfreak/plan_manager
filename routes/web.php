<?php

declare(strict_types=1);

use FrozonFreak\PlanManager\Http\Controllers\Admin\ApprovalController;
use FrozonFreak\PlanManager\Http\Controllers\Admin\DashboardController;
use FrozonFreak\PlanManager\Http\Controllers\Admin\PlanController;
use FrozonFreak\PlanManager\Http\Controllers\Admin\RuleBuilderController;
use FrozonFreak\PlanManager\Http\Controllers\Admin\RuleController;
use FrozonFreak\PlanManager\Http\Controllers\Admin\UsageCorrectionController;
use Illuminate\Support\Facades\Route;

if (config('plan-manager.admin.enabled', false)) {
    Route::prefix(config('plan-manager.admin.route_prefix', 'plan-manager'))
        ->as(config('plan-manager.admin.route_name_prefix', 'plan-manager.'))
        ->middleware(config('plan-manager.admin.middleware', ['web', 'auth']))
        ->group(function (): void {
            Route::get('/', DashboardController::class)->name('dashboard');

            Route::resource('plans', PlanController::class)->only(['index', 'show', 'edit', 'update']);
            Route::resource('rules', RuleController::class)->only(['index', 'create', 'store', 'edit', 'update']);

            Route::get('rule-builder', [RuleBuilderController::class, 'index'])->name('rule-builder.index');
            Route::post('rule-builder/preview', [RuleBuilderController::class, 'preview'])->name('rule-builder.preview');

            Route::get('approvals', [ApprovalController::class, 'index'])->name('approvals.index');
            Route::post('approvals/{planChangeRequest}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
            Route::post('approvals/{planChangeRequest}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');
            Route::post('approvals/{planChangeRequest}/apply', [ApprovalController::class, 'apply'])->name('approvals.apply');

            Route::get('usage-corrections', [UsageCorrectionController::class, 'index'])->name('usage-corrections.index');
            Route::post('usage-corrections', [UsageCorrectionController::class, 'store'])->name('usage-corrections.store');
        });
}
