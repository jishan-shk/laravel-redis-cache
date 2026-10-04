<?php

namespace JishanShk\RedisCache\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use JishanShk\RedisCache\Contracts\PatternDelete;

class FlushRedis
{
    public $pattern;
    public function __construct($pattern)
    {
        $this->pattern = $pattern;
        if (!(Cache::store()->getStore() instanceof PatternDelete)) {
            // dd("Cache class doesn't implement PatternDelete interface. Are you using Redis as your cache driver?");

            return;
        }

        $this->flush();
    }

    protected function flush(): void
    {
        // Adding wildcard at front to ignore redis prefix
        $pattern = $this->pattern ? ('*' . $this->pattern) : '*';
        $prefix = Config::get('database.redis.options.prefix') . Cache::getStore()->getPrefix();
        $keys = Cache::keys($pattern);

        if (empty($keys)) {
            return;
        }

        $formattedKeys = array_map(function ($key) use ($prefix) {
            return str_replace($prefix, '', $key);
        }, $keys);

        sort($formattedKeys);
        foreach($formattedKeys as $key){
            Cache::forget($key);
        }
    }

}
