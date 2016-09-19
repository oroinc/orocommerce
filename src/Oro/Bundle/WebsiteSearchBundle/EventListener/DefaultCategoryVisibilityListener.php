<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationTriggerEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DefaultCategoryVisibilityListener
{
    const CATEGORY_VISIBILITY_FIELD = 'oro_account.category_visibility';

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Trigger full reindex if default category visibility was changed.
     *
     * @param ConfigUpdateEvent $event
     */
    public function onUpdateAfter(ConfigUpdateEvent $event)
    {
        if (array_key_exists(self::CATEGORY_VISIBILITY_FIELD, $event->getChangeSet())) {
            $event = new ReindexationTriggerEvent();
            $this->eventDispatcher->dispatch(ReindexationTriggerEvent::EVENT_NAME, $event);
        }
    }
}
