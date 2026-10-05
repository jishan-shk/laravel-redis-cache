<?php

namespace JishanShk\RedisCache\Tests;

use Illuminate\Support\Facades\Redis;
use JishanShk\RedisCache\RedisCacheServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Redis::connection('default')->flushdb();
    }

    protected function getPackageProviders($app)
    {
        return [RedisCacheServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $redis = [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'database' => 0,
        ];

        $app['config']->set('database.redis.client', env('REDIS_CLIENT', 'phpredis'));
        $app['config']->set('database.redis.options.prefix', 'app_database_');
        $app['config']->set('database.redis.default', $redis);
        $app['config']->set('database.redis.cache', $redis);
        $app['config']->set('database.redis.locks', $redis);

        $app['config']->set('cache.default', 'redis');
        $app['config']->set('cache.prefix', 'app_cache_');
        $app['config']->set('cache.stores.redis', ['driver' => 'redis', 'connection' => 'cache', 'lock_connection' => 'locks']);
        $app['config']->set('cache.stores.other', ['driver' => 'redis', 'connection' => 'cache', 'prefix' => 'other_cache_']);
    }
}
