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
    const CONFIG_LOCALIZATION_ENABLED = 'oro_locale.enabled_localizations';
    const CONFIG_LOCALIZATION_DEFAULT = 'oro_locale.default_localization';

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Trigger full reindex if localizations were changed.
     */
    public function onLocalizationSettingsChange(ConfigUpdateEvent $event)
    {
        if ($event->isChanged(static::CONFIG_LOCALIZATION_DEFAULT) ||
            $event->isChanged(static::CONFIG_LOCALIZATION_ENABLED)
        ) {
            $reindexationEvent = $this->getReindexationRequestEvent($event);
            $this->eventDispatcher->dispatch($reindexationEvent, ReindexationRequestEvent::EVENT_NAME);
        }
    }

    /**
     * ConfigUpdateEvent is being passed, because it contains scope, which can be used with ReindexationRequestEvent
     *
     * @param ConfigUpdateEvent $event
     * @return ReindexationRequestEvent
     */
    protected function getReindexationRequestEvent(ConfigUpdateEvent $event)
    {
        return new ReindexationRequestEvent([], [], [], true, ['main']);
    }
}
