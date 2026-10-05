<?php

namespace JishanShk\RedisCache;

use Illuminate\Cache\CacheManager;
use Illuminate\Support\ServiceProvider;
use JishanShk\RedisCache\Cache\RedisStore;
use JishanShk\RedisCache\Services\ListCacheService;

class RedisCacheServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register()
    {
        $this->app->singleton(ListCacheService::class);

        $this->callAfterResolving('cache', function (CacheManager $cache) {
            // Override the "redis" driver on the existing manager instead of replacing the
            // manager, so drivers registered elsewhere via Cache::extend() are kept
            $cache->extend('redis', function ($app, array $config) {
                // Let Laravel build its own store so every redis option it supports
                // (lock_connection, serializable_classes, events, ...) is applied
                $store = RedisStore::fromStore($this->createRedisDriver($config)->getStore());

                return $this->repository($store, $config);
            });
        });
    }
}
