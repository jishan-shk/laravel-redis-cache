<?php

namespace JishanShk\RedisCache\Tests;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use JishanShk\RedisCache\Cache\FlushRedis;
use JishanShk\RedisCache\Cache\RedisStore;
use JishanShk\RedisCache\Contracts\PatternDelete;
use JishanShk\RedisCache\RedisCacheServiceProvider;

class RedisStoreTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        // Registers a custom driver while booting, before the package provider boots
        return [CustomDriverServiceProvider::class, RedisCacheServiceProvider::class];
    }

    public function test_redis_driver_uses_the_package_store()
    {
        $this->assertInstanceOf(RedisStore::class, Cache::store()->getStore());
        $this->assertInstanceOf(PatternDelete::class, Cache::store('other')->getStore());
    }

    public function test_custom_drivers_registered_by_other_providers_are_kept()
    {
        $this->app['config']->set('cache.stores.custom', ['driver' => 'custom']);

        $this->assertInstanceOf(ArrayStore::class, Cache::store('custom')->getStore());
    }

    public function test_lock_connection_is_respected()
    {
        $this->assertSame('locks', Cache::store()->getStore()->lockConnection()->getName());
        $this->assertSame('cache', Cache::store()->getStore()->connection()->getName());
    }

    public function test_store_prefix_is_respected()
    {
        $this->assertStringStartsWith('other_cache_', Cache::store('other')->getStore()->getPrefix());
    }

    public function test_serializable_classes_is_respected()
    {
        if (! method_exists(CacheManager::class, 'getSerializableClasses')) {
            $this->markTestSkipped('This Laravel version has no cache.serializable_classes option.');
        }

        $this->app['config']->set('cache.serializable_classes', false);
        $this->app['cache']->forgetDriver('redis');

        Cache::put('object', new \ArrayObject([1]), 60);

        $this->assertInstanceOf(\__PHP_Incomplete_Class::class, Cache::get('object'));
    }

    public function test_keys_returns_cache_keys_without_prefixes()
    {
        Cache::put('users:1', 'a', 60);
        Cache::put('users:2', 'b', 60);
        Cache::put('posts:1', 'c', 60);

        $keys = Cache::getStore()->keys('users:*');
        sort($keys);

        $this->assertSame(['users:1', 'users:2'], $keys);
    }

    public function test_forget_by_pattern_handles_keys_containing_colons()
    {
        Cache::put('users:1:profile', 'a', 60);
        Cache::put('users:2:profile', 'b', 60);
        Cache::put('posts:1', 'c', 60);

        $this->assertTrue(Cache::getStore()->forgetByPattern('users:*'));

        $this->assertNull(Cache::get('users:1:profile'));
        $this->assertNull(Cache::get('users:2:profile'));
        $this->assertSame('c', Cache::get('posts:1'));
    }

    public function test_forget_by_pattern_handles_many_keys()
    {
        foreach (range(1, 1200) as $i) {
            Cache::put("bulk:$i", $i, 60);
        }

        Cache::getStore()->forgetByPattern('bulk:*');

        $this->assertSame([], Cache::getStore()->keys('bulk:*'));
    }

    public function test_patterns_only_touch_their_own_store()
    {
        Cache::put('shared', 'default', 60);
        Cache::store('other')->put('shared', 'other', 60);

        new FlushRedis('shared');

        $this->assertNull(Cache::get('shared'));
        $this->assertSame('other', Cache::store('other')->get('shared'));

        new FlushRedis('shared', 'other');

        $this->assertNull(Cache::store('other')->get('shared'));
    }

    public function test_flush_redis_ignores_stores_without_pattern_support()
    {
        $this->app['config']->set('cache.default', 'array');
        Cache::put('key', 'value', 60);

        new FlushRedis('key');

        $this->assertSame('value', Cache::get('key'));
    }
}

class CustomDriverServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Cache::extend('custom', fn () => Cache::repository(new ArrayStore));
    }
}
