<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateCacheTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Sends {@see WebCatalogCalculateCacheTopic} MQ message on oro_web_catalog.web_catalog setting change.
 */
class WebCatalogCacheConfigChangeListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    private const WEB_CATALOG_CONFIGURATION_NAME = 'oro_web_catalog.web_catalog';

    private MessageProducerInterface $messageProducer;

    public function __construct(MessageProducerInterface $messageProducer)
    {
        $this->messageProducer = $messageProducer;
    }

    public function onConfigurationUpdate(ConfigUpdateEvent $event): void
    {
        if (!$this->enabled || !$event->isChanged(self::WEB_CATALOG_CONFIGURATION_NAME)) {
            return;
        }

        $webCatalogId = (int)$event->getNewValue(self::WEB_CATALOG_CONFIGURATION_NAME);
        if ($webCatalogId) {
            $this->messageProducer->send(
                WebCatalogCalculateCacheTopic::getName(),
                [WebCatalogCalculateCacheTopic::WEB_CATALOG_ID => $webCatalogId]
            );
        }
    }
}
