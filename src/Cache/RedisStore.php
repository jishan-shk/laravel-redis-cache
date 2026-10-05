<?php

namespace JishanShk\RedisCache\Cache;

use Generator;
use Illuminate\Cache\RedisStore as LaravelRedisStore;
use Illuminate\Redis\Connections\PhpRedisClusterConnection;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connections\PredisClusterConnection;
use Illuminate\Redis\Connections\PredisConnection;
use JishanShk\RedisCache\Contracts\PatternDelete;
use ReflectionClass;
use RuntimeException;

class RedisStore extends LaravelRedisStore implements PatternDelete
{
    /**
     * Create a pattern-aware store carrying over the state of a Laravel redis store
     *
     * @param  \Illuminate\Cache\RedisStore  $store
     * @return static
     */
    public static function fromStore(LaravelRedisStore $store): static
    {
        $instance = (new ReflectionClass(static::class))->newInstanceWithoutConstructor();

        foreach (get_object_vars($store) as $property => $value) {
            $instance->{$property} = $value;
        }

        return $instance;
    }

    /**
     * Escape Redis glob characters so the value is matched literally
     *
     * @param  string  $value
     * @return string
     */
    public static function escapePattern(string $value): string
    {
        return addcslashes($value, '*?[]\\');
    }

    /**
     * Return the cache keys (without prefixes) matching the pattern
     *
     * @param  string  $pattern
     * @return array
     */
    public function keys(string $pattern = '*'): array
    {
        return array_values(array_unique(iterator_to_array($this->scanKeys($pattern), false)));
    }

    /**
     * Forget every cache key matching the pattern
     *
     * @param  string  $pattern
     * @return bool
     */
    public function forgetByPattern(string $pattern): bool
    {
        $connection = $this->connection();

        foreach (array_chunk($this->keys($pattern), 500) as $chunk) {
            $connection->del(...array_map(fn ($key) => $this->prefix.$key, $chunk));
        }

        return true;
    }

    /**
     * Iterate the cache keys matching the pattern using SCAN
     *
     * @param  string  $pattern
     * @param  int  $count
     * @return \Generator
     */
    protected function scanKeys(string $pattern, int $count = 1000): Generator
    {
        $connection = $this->connection();

        if ($connection instanceof PhpRedisClusterConnection || $connection instanceof PredisClusterConnection) {
            throw new RuntimeException('Pattern based cache deletion is not supported on Redis Cluster connections.');
        }

        // Connections can have a global prefix that SCAN does not apply to the pattern
        $connectionPrefix = match (true) {
            $connection instanceof PhpRedisConnection => $connection->_prefix(''),
            $connection instanceof PredisConnection => (string) ($connection->getOptions()->prefix ?: ''),
            default => '',
        };

        $prefix = $connectionPrefix.$this->getPrefix();

        $cursor = $connection instanceof PhpRedisConnection && version_compare(phpversion('redis'), '6.1.0', '>=')
            ? null
            : '0';

        do {
            $result = $connection->scan($cursor, ['match' => static::escapePattern($prefix).$pattern, 'count' => $count]);

            if (! is_array($result)) {
                break;
            }

            [$cursor, $keys] = $result;

            foreach ((array) $keys as $key) {
                yield substr($key, strlen($prefix));
            }
        } while ((string) $cursor !== '0');
    }
}
