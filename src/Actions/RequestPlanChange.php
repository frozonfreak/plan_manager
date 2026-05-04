<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Actions;

use FrozonFreak\PlanManager\Enums\PlanChangeRequestStatus;
use FrozonFreak\PlanManager\Exceptions\PlanNotFoundException;
use FrozonFreak\PlanManager\Exceptions\PlanVersionNotFoundException;
use FrozonFreak\PlanManager\Models\Plan;
use FrozonFreak\PlanManager\Models\PlanAuditLog;
use FrozonFreak\PlanManager\Models\PlanChangeRequest;
use FrozonFreak\PlanManager\PlanManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class RequestPlanChange
{
    public function __construct(private readonly PreviewPlanChange $preview) {}

    public function handle(Model $subject, string $planCode, ?int $version = null, array $options = []): PlanChangeRequest
    {
        return DB::transaction(function () use ($subject, $planCode, $version, $options): PlanChangeRequest {
            $targetPlan = Plan::query()->where('code', $planCode)->first();
            if (! $targetPlan) {
                throw new PlanNotFoundException("Plan [{$planCode}] was not found.");
            }

            $targetVersion = $targetPlan->activeVersion($version, $options['billing_cycle'] ?? null);
            if (! $targetVersion) {
                throw new PlanVersionNotFoundException("Active version for plan [{$planCode}] was not found.");
            }

            $current = app(PlanManager::class)->for($subject)->assignment();
            $preview = $this->preview->handle([
                'subject' => $subject,
                'plan' => $planCode,
                'version' => $version,
                'billing_cycle' => $options['billing_cycle'] ?? null,
                'addons' => $options['addons'] ?? [],
                'usage' => $options['usage'] ?? [],
            ])->toArray();

            $actor = $options['requested_by'] ?? null;
            $request = PlanChangeRequest::query()->create([
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => (string) $subject->getKey(),
                'current_plan_id' => $current?->plan_id,
                'current_plan_version_id' => $current?->plan_version_id,
                'target_plan_id' => $targetPlan->id,
                'target_plan_version_id' => $targetVersion->id,
                'status' => PlanChangeRequestStatus::Pending,
                'billing_cycle' => $options['billing_cycle'] ?? $targetVersion->billing_cycle,
                'requested_by_type' => $actor instanceof Model ? $actor->getMorphClass() : null,
                'requested_by_id' => $actor instanceof Model ? (string) $actor->getKey() : null,
                'reason' => $options['reason'] ?? null,
                'preview' => $preview,
                'metadata' => $options['metadata'] ?? null,
            ]);

            PlanAuditLog::query()->create([
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => (string) $subject->getKey(),
                'actor_type' => $actor instanceof Model ? $actor->getMorphClass() : null,
                'actor_id' => $actor instanceof Model ? (string) $actor->getKey() : null,
                'event' => 'plan_change.requested',
                'new_values' => ['plan' => $planCode, 'version' => $targetVersion->version],
            ]);

            return $request->refresh();
        });
    }
}
