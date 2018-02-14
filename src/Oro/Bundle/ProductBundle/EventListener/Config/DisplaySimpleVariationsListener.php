<?php

namespace Oro\Bundle\ProductBundle\EventListener\Config;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Component\Cache\Layout\DataProviderCacheCleaner;

class DisplaySimpleVariationsListener
{
    /**
     * @var DataProviderCacheCleaner
     */
    protected $cacheCleaner;

    /**
     * @var DataProviderCacheCleaner
     */
    protected $categoryCacheCleaner;

    /**
     * @var string
     */
    protected $configParameter;

    /**
     * @param DataProviderCacheCleaner $cacheCleaner
     * @param DataProviderCacheCleaner $categoryCacheCleaner
     * @param string $configParameter
     */
    public function __construct(
        DataProviderCacheCleaner $cacheCleaner,
        DataProviderCacheCleaner $categoryCacheCleaner,
        $configParameter
    ) {
        $this->cacheCleaner    = $cacheCleaner;
        $this->categoryCacheCleaner = $categoryCacheCleaner;
        $this->configParameter = $configParameter;
    }

    /**
     * @param ConfigUpdateEvent $event
     */
    public function onUpdateAfter(ConfigUpdateEvent $event)
    {
        if ($event->isChanged($this->configParameter)) {
            $this->cacheCleaner->clearCache();
            $this->categoryCacheCleaner->clearCache();
        }
    }
}
