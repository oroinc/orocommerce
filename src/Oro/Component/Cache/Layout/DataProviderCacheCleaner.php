<?php

namespace Oro\Component\Cache\Layout;

use Doctrine\Common\Cache\CacheProvider;

/**
 * This class is clearing the cache for the whole namespace, used by Data Providers.
 * Has been separated in order to resolve problems with circular dependencies.
 * See example use in CategoryEntityListener.
 */
class DataProviderCacheCleaner
{
    /**
     * @var CacheProvider
     */
    private $cache;

    /**
     * @param CacheProvider $cacheProvider
     */
    public function __construct(CacheProvider $cacheProvider)
    {
        $this->cache = $cacheProvider;
    }

    /**
     * Deletes all stored keys from the cache.
     */
    public function clearCache()
    {
        $this->cache->deleteAll();
    }
}
