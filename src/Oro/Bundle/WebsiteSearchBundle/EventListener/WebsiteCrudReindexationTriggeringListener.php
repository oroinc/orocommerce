<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationTriggerEvent;

/**
 * This listener listens for creation and deletion of Website entity
 * and triggers event telling that indexes with this website should be
 * created or deleted.
 */
class WebsiteCrudReindexationTriggeringListener
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param Website $website
     * @param LifecycleEventArgs $args
     */
    public function postPersist(Website $website, LifecycleEventArgs $args)
    {
        $this->dispatchReindexationTriggeringEvent($website);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preRemove(Website $website, LifecycleEventArgs $args)
    {
        $this->dispatchReindexationTriggeringEvent($website);
    }

    /**
     * @param Website $website
     */
    protected function dispatchReindexationTriggeringEvent(Website $website)
    {
        $event = new ReindexationTriggerEvent(null, $website->getId());

        $this->dispatcher->dispatch(ReindexationTriggerEvent::EVENT_NAME, $event);
    }
}
