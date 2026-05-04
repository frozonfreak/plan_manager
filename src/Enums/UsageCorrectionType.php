<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Enums;

enum UsageCorrectionType: string
{
    case Adjustment = 'adjustment';
    case SetTo = 'set_to';
}
