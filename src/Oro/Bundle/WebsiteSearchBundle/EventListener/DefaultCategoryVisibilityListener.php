<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\AccountBundle\Visibility\Resolver\CategoryVisibilityResolver;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationTriggerEvent;

class DefaultCategoryVisibilityListener
{
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
        if ($event->isChanged(CategoryVisibilityResolver::OPTION_CATEGORY_VISIBILITY)) {
            $reindexationEvent = new ReindexationTriggerEvent();
            $this->eventDispatcher->dispatch(ReindexationTriggerEvent::EVENT_NAME, $reindexationEvent);
        }
    }
}
