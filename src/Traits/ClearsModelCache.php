<?php

namespace JishanShk\RedisCache\Traits;

use JishanShk\RedisCache\Support\CacheInvalidationSuppressor;

trait ClearsModelCache
{
    /**
     * Cache tags to clear for this model
     * 
     * @var array
     */
    // protected $cacheTags = [];

    /**
     * Clear all cached items for this model
     * 
     * @return void
     */
    protected function clearCache()
    {
        if (CacheInvalidationSuppressor::isSuppressed()) {
            return;
        }

        foreach ($this->cacheTags ?? [] as $tag) {
            $this->clearCacheByTag($tag);
        }
    }
    
    /**
     * Clear cache for a specific tag
     * 
     * @param string $tag
     * @return void
     */
    protected function clearCacheByTag(string $tag)
    {
        clearCacheByPattern($tag);
    }

    /**
     * Handle the model "saved" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function saved($model)
    {
        $this->clearCache();
    }

    /**
     * Handle the model "deleted" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function deleted($model)
    {
        $this->clearCache();
    }
}
