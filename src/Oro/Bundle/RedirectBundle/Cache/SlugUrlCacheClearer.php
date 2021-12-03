<?php

namespace Oro\Bundle\RedirectBundle\Cache;

use Doctrine\Common\Cache\ClearableCache;
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
        if ($this->cache instanceof ClearableCache) {
            $this->cache->deleteAll();
        }
    }
}
