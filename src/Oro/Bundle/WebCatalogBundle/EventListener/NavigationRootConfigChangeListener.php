<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\WebCatalogBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Clears nodes items cache when navigation root changed
 */
class NavigationRootConfigChangeListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    /** @var CacheProvider */
    private $layoutCacheProvider;

    /** @var MessageProducerInterface */
    private $messageProducer;

    /**
     * @param CacheProvider $layoutCacheProvider
     * @param MessageProducerInterface $messageProducer
     */
    public function __construct(CacheProvider $layoutCacheProvider, MessageProducerInterface $messageProducer)
    {
        $this->layoutCacheProvider = $layoutCacheProvider;
        $this->messageProducer = $messageProducer;
    }

    /**
     * @param ConfigUpdateEvent $event
     */
    public function onConfigUpdate(ConfigUpdateEvent $event)
    {
        if (!$this->enabled || !$event->isChanged('oro_web_catalog.navigation_root')) {
            return;
        }

        $contentNodeId = $event->getNewValue('oro_web_catalog.navigation_root');
        if ($contentNodeId) {
            $this->messageProducer->send(Topics::CALCULATE_CONTENT_NODE_CACHE, [
                'contentNodeId' => (int)$contentNodeId
            ]);
        }

        $this->layoutCacheProvider->deleteAll();
    }
}
