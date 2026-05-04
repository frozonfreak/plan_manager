<?php

declare(strict_types=1);

namespace FrozonFreak\PlanManager\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

final class EntitlementCache
{
    public static function key(Model $subject): string
    {
        return 'plan-manager:entitlements:v'.self::version().':'.$subject->getMorphClass().':'.$subject->getKey();
    }

    public static function forget(Model $subject): void
    {
        Cache::store(config('plan-manager.cache.store'))->forget(self::key($subject));
    }

    public static function bumpVersion(): void
    {
        $store = Cache::store(config('plan-manager.cache.store'));
        $key = 'plan-manager:entitlements:version';

        if (! $store->has($key)) {
            $store->forever($key, 1);
        }

        $store->increment($key);
    }

    private static function version(): int
    {
        return (int) Cache::store(config('plan-manager.cache.store'))->rememberForever('plan-manager:entitlements:version', fn (): int => 1);
    }
}
