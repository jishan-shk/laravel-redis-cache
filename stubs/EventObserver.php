<?php

namespace App\Observers;

use JishanShk\RedisCache\Traits\ClearsModelCache;

class EventObserver
{
    use ClearsModelCache;

    /**
     * Cache tags to clear for this model
     *
     * @var array
     */
    protected $cacheTags = [];

    public function __construct()
    {
        $year = now()->year;
        $this->cacheTags = [
        ];
    }
}