<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Enums;

enum FeatureType: string
{
    case Boolean = 'boolean';
    case Integer = 'integer';
    case Decimal = 'decimal';
    case String = 'string';
    case Json = 'json';
}
