# jishan-shk/laravel-redis-cache

Laravel Redis cache store with pattern/tag based invalidation, plus a trait for clearing cache from model observers.

## Installation

```bash
composer require jishan-shk/laravel-redis-cache
```

The service provider is auto-discovered. It swaps Laravel's `cache` manager so the `redis` driver uses a store that supports `keys()` and `forgetByPattern()`. Set `CACHE_STORE=redis` (or `CACHE_DRIVER=redis` on Laravel 10).

## Usage

### Tagged list cache

```php
use JishanShk\RedisCache\Services\ListCacheService;

$events = app(ListCacheService::class)->remember('events:list', ['events'], 3600, fn () => Event::all());

// Forget every key tagged "events"
clearCacheByPattern('events');
invalidateListCacheTags(['events', 'calendar']);
```

### Clearing cache from an observer

```php
use JishanShk\RedisCache\Traits\ClearsModelCache;

class EventObserver
{
    use ClearsModelCache;

    protected $cacheTags = ['events'];
}
```

The tags are cleared on `saved` and `deleted`. See `stubs/EventObserver.php`.

### Suppressing invalidation

```php
use JishanShk\RedisCache\Support\CacheInvalidationSuppressor;

CacheInvalidationSuppressor::suppress(function () {
    // bulk writes; observers won't clear cache here
});
clearCacheByPattern('events'); // clear once afterwards
```

### Flush by raw pattern

```php
use JishanShk\RedisCache\Cache\FlushRedis;

new FlushRedis('events:');
```
