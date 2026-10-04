<?php


use JishanShk\RedisCache\Services\ListCacheService;

if (!function_exists('generateTaggedCacheKey')) {
    function generateTaggedCacheKey($pattern)
    {
        return '-tag#' . $pattern . '#tag-';
    }
}
if (!function_exists('clearCacheByPattern')) {
    function clearCacheByPattern($pattern)
    {
        app(ListCacheService::class)->invalidate($pattern);
    }
}

if (!function_exists('invalidateListCacheTags')) {
    function invalidateListCacheTags(array $tags): void
    {
        foreach ($tags as $tag) {
            clearCacheByPattern($tag);
        }
    }
}