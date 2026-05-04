<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Actions;

use FrozonFreak\PlanManager\Data\EntitlementResult;
use FrozonFreak\PlanManager\Support\EntitlementCache;
use Illuminate\Database\Eloquent\Model;

final class RecalculateEntitlementSnapshot
{
    public function __construct(private readonly ResolveEntitlements $resolve) {}

    public function handle(Model $subject): EntitlementResult
    {
        EntitlementCache::forget($subject);

        return $this->resolve->handle($subject, true);
    }
}
