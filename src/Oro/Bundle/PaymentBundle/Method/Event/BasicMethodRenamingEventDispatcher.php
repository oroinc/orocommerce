<?php

namespace Oro\Bundle\PaymentBundle\Method\Event;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Dispatches payment method renaming events.
 *
 * This dispatcher creates and dispatches {@see MethodRenamingEvent} instances when a payment
 * method identifier changes, notifying all registered listeners of the change.
 */
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

    #[\Override]
    public function dispatch($oldId, $newId)
    {
        $this->eventDispatcher->dispatch(new MethodRenamingEvent($oldId, $newId), MethodRenamingEvent::NAME);
    }
}
