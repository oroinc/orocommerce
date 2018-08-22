<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Doctrine\Common\Cache\CacheProvider;

class ProductAvailabilityCacheListener
{
    /**
     * @var CacheProvider
     */
    private $cache;
    /**
     * @param CacheProvider $cache
     */
    public function __construct(CacheProvider $cache)
    {
        $this->cache = $cache;
    }
    public function postFlush()
    {
        $this->cache->deleteAll();
    }
}
