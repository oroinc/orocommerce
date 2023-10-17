<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Dispatches oro_website_search.reindexation_request event on oro_web_catalog.web_catalog setting change.
 */
class WebCatalogConfigChangeListener
{
    private EventDispatcherInterface $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function onConfigurationUpdate(ConfigUpdateEvent $event): void
    {
        if (!$event->isChanged('oro_web_catalog.web_catalog')) {
            return;
        }

        $reindexationEvent = $this->getReindexationRequestEvent($event);
        $this->dispatcher->dispatch($reindexationEvent, ReindexationRequestEvent::EVENT_NAME);
    }

    protected function getReindexationRequestEvent(ConfigUpdateEvent $event): ReindexationRequestEvent
    {
        return new ReindexationRequestEvent([], [], [], true, ['main']);
    }
}
