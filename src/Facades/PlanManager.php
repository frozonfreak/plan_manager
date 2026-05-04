<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Facades;

use FrozonFreak\PlanManager\Data\EntitlementResult;
use FrozonFreak\PlanManager\Data\PreviewResult;
use FrozonFreak\PlanManager\Models\PlanChangeRequest;
use FrozonFreak\PlanManager\Models\SubscriptionAssignment;
use FrozonFreak\PlanManager\Models\UsageCorrection;
use FrozonFreak\PlanManager\PlanContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * @method static PlanContext for(Model $subject)
 * @method static PreviewResult preview(array $input)
 * @method static SubscriptionAssignment assign(Model $subject, string $planCode, ?int $version = null, array $options = [])
 * @method static SubscriptionAssignment change(Model $subject, string $planCode, ?int $version = null, array $options = [])
 * @method static EntitlementResult recalculate(Model $subject)
 * @method static SubscriptionAssignment startTrial(Model $subject, string $planCode, string $trialType, array $options = [])
 * @method static SubscriptionAssignment endTrial(Model $subject, string $reason = 'manual_end')
 * @method static SubscriptionAssignment extendTrial(Model $subject, int $days)
 * @method static SubscriptionAssignment convertTrial(Model $subject, array $options = [])
 * @method static UsageCorrection correctUsage(Model $subject, string $meterCode, float|int $quantity, string $reason, array $options = [])
 * @method static PlanChangeRequest requestChange(Model $subject, string $planCode, ?int $version = null, array $options = [])
 * @method static PlanChangeRequest approveChange(PlanChangeRequest $request, ?Model $reviewer = null, ?string $note = null)
 * @method static PlanChangeRequest rejectChange(PlanChangeRequest $request, ?Model $reviewer = null, ?string $note = null)
 * @method static SubscriptionAssignment applyApprovedChange(PlanChangeRequest $request, array $options = [])
 */
final class PlanManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \FrozonFreak\PlanManager\PlanManager::class;
    }
}
