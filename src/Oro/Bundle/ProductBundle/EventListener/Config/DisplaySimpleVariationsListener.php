<?php

namespace Oro\Bundle\ProductBundle\EventListener\Config;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Clears caches for product and category layout data providers
 * when a specified configuration option is changed.
 */
class DisplaySimpleVariationsListener
{
    private CacheInterface $productCache;
    private CacheInterface $categoryCache;
    private string $configParameter;

    public function __construct(
        CacheInterface $productCache,
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
            $this->productCache->clear();
            $this->categoryCache->clear();
        }
    }
}
