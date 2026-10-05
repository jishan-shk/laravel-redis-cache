# jishan-shk/laravel-redis-cache

Laravel Redis cache store with pattern/tag based invalidation, plus a trait for clearing cache from model observers.

## Installation

```bash
composer require jishan-shk/laravel-redis-cache
```

Supports Laravel 10, 11, 12 and 13 with either the `phpredis` or `predis` client.

The service provider is auto-discovered. It overrides the `redis` cache driver so redis stores support `keys()` and `forgetByPattern()`. Laravel still builds the store, so options such as `lock_connection` and `serializable_classes` keep working. Set `CACHE_STORE=redis` (or `CACHE_DRIVER=redis` on Laravel 10).

Keys are found with `SCAN`, never `KEYS`. Redis Cluster connections are not supported.

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

Tags are matched literally, so a tag may contain characters like `*` or `?`.

### Pattern helpers on the store

Patterns use Redis glob syntax and are matched against cache keys within the store's prefix.

```php
Cache::getStore()->keys('users:*');            // ['users:1', 'users:2'], without prefixes
Cache::getStore()->forgetByPattern('users:*');
Cache::store('other')->getStore()->forgetByPattern('*');
```

### Flush by raw pattern

```php
use JishanShk\RedisCache\Cache\FlushRedis;

new FlushRedis('events:');          // keys ending in "events:" on the default store
new FlushRedis('events:*', 'other'); // on the "other" store
```

`FlushRedis` does nothing when the store is not a redis store, such as the `array` store in tests.

## Testing

The test suite needs a running Redis server:

```bash
composer install
REDIS_HOST=127.0.0.1 REDIS_CLIENT=phpredis composer test
```
