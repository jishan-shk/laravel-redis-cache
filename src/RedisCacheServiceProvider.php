<?php

namespace JishanShk\RedisCache;

use Illuminate\Support\ServiceProvider;
use JishanShk\RedisCache\Cache\CacheManager;
use JishanShk\RedisCache\Services\ListCacheService;

class RedisCacheServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register()
    {
        $this->app->singleton(ListCacheService::class);
    }

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->app->extend('cache', function ($service, $app) {
            return new CacheManager($app);
        });
    }
}
