<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

/**
 * This listener listens for creation and deletion of Website entity
 * and triggers event telling that indexes with this website should be
 * created or deleted.
 */
class WebsiteReindexationOnCreateDeleteListener
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
     */
    public function postPersist(Website $website)
    {
        $this->dispatchReindexationRequestEvent($website);
    }

    /**
     * @param Website $website
     */
    public function preRemove(Website $website)
    {
        $this->dispatchReindexationRequestEvent($website);
    }

    /**
     * @param Website $website
     */
    protected function dispatchReindexationRequestEvent(Website $website)
    {
        $event = new ReindexationRequestEvent(null, $website->getId());

        $this->dispatcher->dispatch(ReindexationRequestEvent::EVENT_NAME, $event);
    }
}
