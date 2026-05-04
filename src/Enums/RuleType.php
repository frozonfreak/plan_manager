<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Enums;

enum RuleType: string
{
    case Eligibility = 'eligibility';
    case DiscountDisplay = 'discount_display';
    case UsageLimit = 'usage_limit';
    case AddonAvailability = 'addon_availability';
    case UpgradePath = 'upgrade_path';
    case DowngradePath = 'downgrade_path';
    case Trial = 'trial';
    case TrialEligibility = 'trial_eligibility';
    case TrialExtension = 'trial_extension';
    case TrialConversion = 'trial_conversion';
    case TrialUsageLimit = 'trial_usage_limit';
    case Grandfathering = 'grandfathering';
}
