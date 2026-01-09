<?php

namespace Oro\Bundle\ShippingBundle\Method\Event;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Dispatches shipping method removal events.
 *
 * This dispatcher creates and dispatches {@see MethodRemovalEvent} instances when shipping methods are removed
 * from the system, notifying listeners to perform cleanup operations.
 */
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
