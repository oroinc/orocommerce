<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Dispatches oro_website_search.reindexation_request event on oro_locale.default_localization setting change.
 */
class WebsiteLocalizationConfigListener
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function onLocalizationSettingsChange(ConfigUpdateEvent $event): void
    {
        if (
            $event->isChanged('oro_locale.default_localization')
            || $event->isChanged('oro_locale.enabled_localizations')
        ) {
            $reindexationEvent = $this->getReindexationRequestEvent($event);
            $this->eventDispatcher->dispatch($reindexationEvent, ReindexationRequestEvent::EVENT_NAME);
        }
    }

    protected function getReindexationRequestEvent(ConfigUpdateEvent $event): ReindexationRequestEvent
    {
        return new ReindexationRequestEvent([], [], [], true, ['main']);
    }
}
