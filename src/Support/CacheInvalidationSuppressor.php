<?php

namespace JishanShk\RedisCache\Support;

class CacheInvalidationSuppressor
{
    /**
     * Nesting depth of active suppressions
     *
     * @var int
     */
    protected static $depth = 0;

    /**
     * Run the callback without clearing model cache on save/delete
     *
     * @param  callable  $callback
     * @return mixed
     */
    public static function suppress(callable $callback)
    {
        static::$depth++;

        try {
            return $callback();
        } finally {
            static::$depth--;
        }
    }

    /**
     * Check whether cache invalidation is currently suppressed
     *
     * @return bool
     */
    public static function isSuppressed(): bool
    {
        return static::$depth > 0;
    }
}
