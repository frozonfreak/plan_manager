<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Enums;

enum PlanVersionStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}
