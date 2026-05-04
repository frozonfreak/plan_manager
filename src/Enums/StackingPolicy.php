<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Enums;

enum StackingPolicy: string
{
    case Exclusive = 'exclusive';
    case CanStack = 'can_stack';
    case StopProcessing = 'stop_processing';
}
