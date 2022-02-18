<?php

namespace Oro\Bundle\RedirectBundle\Cache;

use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

/**
 * Clears Slug Url cache.
 */
class SlugUrlCacheClearer implements CacheClearerInterface
{
    private UrlCacheInterface $cache;

    public function __construct(UrlCacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function clear(string $cacheDir)
    {
        if ($this->cache instanceof ClearableCacheInterface) {
            $this->cache->deleteAll();
        }
    }
}
