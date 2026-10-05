<?php

namespace JishanShk\RedisCache\Tests;

use Illuminate\Support\Facades\Cache;
use JishanShk\RedisCache\Services\ListCacheService;
use JishanShk\RedisCache\Support\CacheInvalidationSuppressor;
use JishanShk\RedisCache\Traits\ClearsModelCache;

class ListCacheServiceTest extends TestCase
{
    public function test_remember_caches_and_invalidate_clears_tagged_keys()
    {
        $service = app(ListCacheService::class);

        $this->assertSame('events', $service->remember('events:list', ['events'], 60, fn () => 'events'));
        $this->assertSame('events', $service->remember('events:list', ['events'], 60, fn () => 'fresh'));
        $service->remember('calendar:list', ['calendar', 'events'], 60, fn () => 'calendar');
        $service->remember('news:list', ['news'], 60, fn () => 'news');
        Cache::put('plain', 'value', 60);

        clearCacheByPattern('events');

        $this->assertNull(Cache::get($service->key('events:list', ['events'])));
        $this->assertNull(Cache::get($service->key('calendar:list', ['calendar', 'events'])));
        $this->assertSame('news', Cache::get($service->key('news:list', ['news'])));
        $this->assertSame('value', Cache::get('plain'));
    }

    public function test_tags_are_matched_exactly()
    {
        $service = app(ListCacheService::class);

        $service->remember('a', ['event'], 60, fn () => 'a');
        $service->remember('b', ['events'], 60, fn () => 'b');
        $service->remember('c', ['ev*'], 60, fn () => 'c');

        invalidateListCacheTags(['ev*', 'event']);

        $this->assertNull(Cache::get($service->key('a', ['event'])));
        $this->assertSame('b', Cache::get($service->key('b', ['events'])));
        $this->assertNull(Cache::get($service->key('c', ['ev*'])));
    }

    public function test_observer_trait_clears_tags_unless_suppressed()
    {
        $service = app(ListCacheService::class);
        $observer = new class {
            use ClearsModelCache;

            protected $cacheTags = ['events'];
        };

        $service->remember('events:list', ['events'], 60, fn () => 'events');

        CacheInvalidationSuppressor::suppress(fn () => $observer->saved(null));
        $this->assertSame('events', Cache::get($service->key('events:list', ['events'])));

        $observer->deleted(null);
        $this->assertNull(Cache::get($service->key('events:list', ['events'])));
    }
}
