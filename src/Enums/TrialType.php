<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Enums;

enum TrialType: string
{
    case TimeLimited = 'time_limited';
    case UsageLimited = 'usage_limited';
    case TimeAndUsageLimited = 'time_and_usage_limited';
    case Manual = 'manual';
}
