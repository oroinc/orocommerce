<?php

namespace Oro\Bundle\ShippingBundle\Method\Event;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Dispatches shipping method type removal events.
 *
 * This dispatcher creates and dispatches {@see MethodTypeRemovalEvent} instances when shipping method types are removed
 * from the system, notifying listeners to perform cleanup operations.
 */
class BasicMethodTypeRemovalEventDispatcher implements MethodTypeRemovalEventDispatcherInterface
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
    public function dispatch($methodId, $typeId)
    {
        $this->eventDispatcher->dispatch(new MethodTypeRemovalEvent($methodId, $typeId), MethodTypeRemovalEvent::NAME);
    }
}
