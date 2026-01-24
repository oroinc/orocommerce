<?php

namespace Oro\Bundle\TaxBundle\Event;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Dispatches context mapping events for tax calculation.
 *
 * This dispatcher is responsible for triggering {@see ContextEvent} events that allow event listeners
 * to populate the tax context with additional data needed for tax calculations.
 * The context is built by mapping domain objects (such as orders or line items) to tax-specific data
 * like tax codes, addresses, and other contextual information.
 */
class ContextEventDispatcher
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

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

        $this->eventDispatcher->dispatch($event, ContextEvent::NAME);

        return $event->getContext();
    }
}
