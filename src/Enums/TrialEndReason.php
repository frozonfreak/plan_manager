<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Enums;

enum TrialEndReason: string
{
    case ManualEnd = 'manual_end';
    case TimeExpired = 'time_expired';
    case UsageExhausted = 'usage_exhausted';
    case Converted = 'converted';
    case Cancelled = 'cancelled';
}
