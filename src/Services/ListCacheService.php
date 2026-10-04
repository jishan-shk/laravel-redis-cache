<?php

namespace JishanShk\RedisCache\Services;

use Illuminate\Support\Facades\Cache;
use JishanShk\RedisCache\Cache\FlushRedis;

class ListCacheService
{
    /**
     * Build a cache key that carries the given tags
     *
     * @param  string  $key
     * @param  array  $tags
     * @return string
     */
    public function key(string $key, array $tags = []): string
    {
        foreach ($tags as $tag) {
            $key .= generateTaggedCacheKey($tag);
        }

        return $key;
    }

    /**
     * Get an item from the cache, or store the callback result under a tagged key
     *
     * @param  string  $key
     * @param  array  $tags
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @param  \Closure  $callback
     * @return mixed
     */
    public function remember(string $key, array $tags, $ttl, \Closure $callback)
    {
        return Cache::remember($this->key($key, $tags), $ttl, $callback);
    }

    /**
     * Forget every cached item carrying the given tag
     *
     * @param  string  $tag
     * @return void
     */
    public function invalidate(string $tag): void
    {
        new FlushRedis(generateTaggedCacheKey($tag) . '*');
    }
}
