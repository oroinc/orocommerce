<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;

/**
 * Clears nodes items cache when navigation root changed
 */
class NavigationRootConfigChangeListener
{
    /** @var CacheProvider */
    private $layoutCacheProvider;

    /**
     * @param CacheProvider $layoutCacheProvider
     */
    public function __construct(CacheProvider $layoutCacheProvider)
    {
        $this->layoutCacheProvider = $layoutCacheProvider;
    }

    /**
     * @param ConfigUpdateEvent $event
     */
    public function onConfigUpdate(ConfigUpdateEvent $event)
    {
        if (!$event->isChanged('oro_web_catalog.navigation_root')) {
            return;
        }

        $this->layoutCacheProvider->deleteAll();
    }
}
