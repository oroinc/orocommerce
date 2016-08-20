<?php

namespace Oro\Bundle\TaxBundle\Event;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ContextEventDispatcher
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param object $object
     * @return \ArrayObject
     */
    public function dispatch($object)
    {
        $event = new ContextEvent($object);

        $this->eventDispatcher->dispatch(ContextEvent::NAME, $event);

        return $event->getContext();
    }
}
