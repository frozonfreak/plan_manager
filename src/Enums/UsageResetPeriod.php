<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Enums;

enum UsageResetPeriod: string
{
    case Never = 'never';
    case Daily = 'daily';
    case Monthly = 'monthly';
    case Yearly = 'yearly';
}
