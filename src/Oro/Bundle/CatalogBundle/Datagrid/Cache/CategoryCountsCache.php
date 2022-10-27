<?php

namespace Oro\Bundle\CatalogBundle\Datagrid\Cache;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Psr\Cache\CacheItemPoolInterface;

/**
 * The cache for different kind of aggregated info for categories.
 * Cache counts by website and customer user id.
 */
class CategoryCountsCache
{
    private CacheItemPoolInterface $cacheProvider;
    private TokenAccessor $tokenAccessor;
    private WebsiteManager $websiteManager;

    public function __construct(
        CacheItemPoolInterface $cacheProvider,
        TokenAccessor $tokenAccessor,
        WebsiteManager $websiteManager
    ) {
        $this->cacheProvider = $cacheProvider;
        $this->tokenAccessor = $tokenAccessor;
        $this->websiteManager = $websiteManager;
    }

    public function getCounts(string $key) : array|null
    {
        $key = $this->getDataKey($key);
        $cacheItem = $this->cacheProvider->getItem($key);

        return $cacheItem->isHit() ? $cacheItem->get() : null;
    }

    public function setCounts(string $key, array $counts, int $lifeTime = 0) : void
    {
        $key = $this->getDataKey($key);
        $cacheItem = $this->cacheProvider->getItem($key);
        $cacheItem->set($counts)->expiresAfter($lifeTime);
        $this->cacheProvider->save($cacheItem);
    }

    protected function getDataKey(string $key) : string
    {
        $websiteId = null;
        $website = $this->websiteManager->getCurrentWebsite();
        if ($website) {
            $websiteId = $website->getId();
        }

        return UniversalCacheKeyGenerator::normalizeCacheKey(
            sprintf('%s|%d|%d', $key, $websiteId, $this->tokenAccessor->getUserId())
        );
    }
}
