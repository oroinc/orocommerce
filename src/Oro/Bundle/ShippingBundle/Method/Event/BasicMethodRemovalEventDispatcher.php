<?php

namespace Oro\Bundle\ShippingBundle\Method\Event;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BasicMethodRemovalEventDispatcher implements MethodRemovalEventDispatcherInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    #[\Override]
    public function dispatch($id)
    {
        $this->eventDispatcher->dispatch(new MethodRemovalEvent($id), MethodRemovalEvent::NAME);
    }
}
