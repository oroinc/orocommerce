<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

class WebsiteLocalizationConfigListener
{
    const CONFIG_LOCALIZATION_ENABLED = 'oro_locale.enabled_localizations';
    const CONFIG_LOCALIZATION_DEFAULT = 'oro_locale.default_localization';

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Trigger full reindex if localizations were changed.
     *
     * @param ConfigUpdateEvent $event
     */
    public function onLocalizationSettingsChange(ConfigUpdateEvent $event)
    {
        if ($event->isChanged(static::CONFIG_LOCALIZATION_DEFAULT) ||
            $event->isChanged(static::CONFIG_LOCALIZATION_ENABLED)
        ) {
            $reindexationEvent = new ReindexationRequestEvent();
            $this->eventDispatcher->dispatch(ReindexationRequestEvent::EVENT_NAME, $reindexationEvent);
        }
    }
}
