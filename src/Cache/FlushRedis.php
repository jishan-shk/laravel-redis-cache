<?php

namespace JishanShk\RedisCache\Cache;

use Illuminate\Support\Facades\Cache;
use JishanShk\RedisCache\Contracts\PatternDelete;

class FlushRedis
{
    public $pattern;

    public $store;

    public function __construct($pattern, $store = null)
    {
        $this->pattern = $pattern;
        $this->store = $store;

        $this->flush();
    }

    protected function flush(): void
    {
        $store = Cache::store($this->store)->getStore();

        // Pattern deletion is only available on the redis store; other stores are left untouched
        if (! $store instanceof PatternDelete) {
            return;
        }

        $store->forgetByPattern($this->pattern ? ('*' . $this->pattern) : '*');
    }
}
