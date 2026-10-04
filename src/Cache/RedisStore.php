<?php

namespace JishanShk\RedisCache\Cache;

use Illuminate\Cache\RedisStore as LaravelRedisStore;
use JishanShk\RedisCache\Contracts\PatternDelete;

class RedisStore extends LaravelRedisStore implements PatternDelete
{
    public function keys(string $pattern = '*'): array
    {
        return $this->connection()->keys($pattern);
    }

    public function forgetByPattern(string $pattern): bool
    {
        foreach ($this->keys($pattern) as $item) {

            $item_exploded = explode(':', $item);

            if (count($item_exploded) < 2) {
                continue;
            }
            $this->forget($item_exploded[1]);
        }

        return true;
    }
}
