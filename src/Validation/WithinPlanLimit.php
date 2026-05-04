<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Validation;

use Closure;
use FrozonFreak\PlanManager\Facades\PlanManager;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

final class WithinPlanLimit implements ValidationRule
{
    public function __construct(private readonly Model $subject, private readonly string $featureCode) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $limit = PlanManager::for($this->subject)->limit($this->featureCode);
        if ($limit !== null && (float) $limit >= 0 && (float) $value > (float) $limit) {
            $fail("The {$attribute} exceeds the plan limit.");
        }
    }
}
