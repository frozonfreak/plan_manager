<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Http\Middleware;

use Closure;
use FrozonFreak\PlanManager\PlanManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class EnsureWithinPlanLimit
{
    public function __construct(private readonly PlanManager $plans) {}

    public function handle(Request $request, Closure $next, string $featureCode, float|int|string $value): mixed
    {
        $subject = $this->subject($request);
        abort_unless($subject, 403);
        $this->plans->for($subject)->ensureWithinLimit($featureCode, (float) $value);

        return $next($request);
    }

    private function subject(Request $request): ?Model
    {
        $resolver = config('plan-manager.subject_resolver');
        if (is_callable($resolver)) {
            $resolved = $resolver($request);

            return $resolved instanceof Model ? $resolved : null;
        }

        return $request->user();
    }
}
