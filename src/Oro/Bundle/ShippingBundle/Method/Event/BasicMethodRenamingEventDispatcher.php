<?php

namespace Oro\Bundle\ShippingBundle\Method\Event;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BasicMethodRenamingEventDispatcher implements MethodRenamingEventDispatcherInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch($oldId, $newId)
    {
        $this->eventDispatcher->dispatch(new MethodRenamingEvent($oldId, $newId), MethodRenamingEvent::NAME);
    }
}
