<?php

namespace Oro\Bundle\ProductBundle\EventListener\Config;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;

/**
 * Clears caches for product and category layout data providers
 * when a specified configuration option is changed.
 */
class DisplaySimpleVariationsListener
{
    /** @var CacheProvider */
    private $productCache;

    /** @var CacheProvider */
    private $categoryCache;

    /** @var string */
    private $configParameter;

    /**
     * @param CacheProvider $productCache
     * @param CacheProvider $categoryCache
     * @param string        $configParameter
     */
    public function __construct(
        CacheProvider $productCache,
        CacheProvider $categoryCache,
        $configParameter
    ) {
        $this->productCache = $productCache;
        $this->categoryCache = $categoryCache;
        $this->configParameter = $configParameter;
    }

    public function onUpdateAfter(ConfigUpdateEvent $event)
    {
        if ($event->isChanged($this->configParameter)) {
            $this->productCache->deleteAll();
            $this->categoryCache->deleteAll();
        }
    }
}
