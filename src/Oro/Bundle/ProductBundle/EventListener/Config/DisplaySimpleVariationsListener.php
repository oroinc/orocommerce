<?php

namespace Oro\Bundle\ProductBundle\EventListener\Config;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Clears caches for product and category layout data providers
 * when a specified configuration option is changed.
 */
class DisplaySimpleVariationsListener
{
    private CacheProvider $productCache;
    private CacheInterface $categoryCache;
    private string $configParameter;

    public function __construct(
        CacheProvider $productCache,
        CacheInterface $categoryCache,
        $configParameter
    ) {
        $this->productCache = $productCache;
        $this->categoryCache = $categoryCache;
        $this->configParameter = $configParameter;
    }

    public function onUpdateAfter(ConfigUpdateEvent $event) : void
    {
        if ($event->isChanged($this->configParameter)) {
            $this->productCache->deleteAll();
            $this->categoryCache->clear();
        }
    }
}
