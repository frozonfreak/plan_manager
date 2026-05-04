<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Enums;

enum PlanChangeRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Applied = 'applied';
    case Cancelled = 'cancelled';
}
