<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Enums;

enum AddonType: string
{
    case Recurring = 'recurring';
    case OneTime = 'one_time';
    case UsagePack = 'usage_pack';
    case FeatureUnlock = 'feature_unlock';
}
