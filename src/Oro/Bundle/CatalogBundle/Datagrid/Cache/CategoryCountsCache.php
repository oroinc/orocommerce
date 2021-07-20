<?php

namespace Oro\Bundle\CatalogBundle\Datagrid\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

/**
 * The cache for different kind of aggregated info for categories.
 * Cache counts by website and customer user id.
 */
class CategoryCountsCache
{
    /** @var CacheProvider */
    protected $cacheProvider;

    /** @var TokenAccessor */
    protected $tokenAccessor;

    /** @var WebsiteManager */
    private $websiteManager;

    public function __construct(
        CacheProvider $cacheProvider,
        TokenAccessor $tokenAccessor,
        WebsiteManager $websiteManager
    ) {
        $this->cacheProvider = $cacheProvider;
        $this->tokenAccessor = $tokenAccessor;
        $this->websiteManager = $websiteManager;
    }

    /**
     * @param string $key
     * @return array|null
     */
    public function getCounts($key)
    {
        $key = $this->getDataKey($key);
        $counts = $this->cacheProvider->fetch($key);

        return false !== $counts ? $counts : null;
    }

    /**
     * @param string $key
     * @param array $counts
     * @param int $lifeTime
     */
    public function setCounts($key, array $counts, $lifeTime = 0)
    {
        $key = $this->getDataKey($key);

        $this->cacheProvider->save($key, $counts, $lifeTime);
    }

    /**
     * @param string $key
     * @return string
     */
    protected function getDataKey($key)
    {
        $websiteId = null;
        $website = $this->websiteManager->getCurrentWebsite();
        if ($website) {
            $websiteId = $website->getId();
        }

        return sprintf('%s|%d|%d', $key, $websiteId, $this->tokenAccessor->getUserId());
    }
}
