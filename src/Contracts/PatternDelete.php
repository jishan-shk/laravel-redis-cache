<?php

namespace JishanShk\RedisCache\Contracts;

interface PatternDelete
{
    /**
     * Return the cache keys (without prefixes) matching the pattern.
     *
     * @param  string  $pattern
     * @return array
     */
    public function keys(string $pattern = '*'): array;

    /**
     * Forget every cache key matching the pattern.
     *
     * @param  string  $pattern
     * @return bool
     */
    public function forgetByPattern(string $pattern): bool;
}
