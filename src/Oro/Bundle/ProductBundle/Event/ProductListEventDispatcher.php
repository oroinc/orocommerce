<?php

namespace Oro\Bundle\ProductBundle\Event;

use Symfony\Component\EventDispatcher\ImmutableEventDispatcher;

/**
 * Dispatches a product list related events.
 * This event dispatcher dispatches two events for each type of a product list related event.
 * At first a common event is dispatched. Listeners for this event are executed for all price lists.
 * Next an event for a particular product list is dispatched. Listeners for this event are executed
 * to a particular product list only.
 */
class ProductListEventDispatcher extends ImmutableEventDispatcher
{
    /**
     * {@inheritDoc}
     *
     * @param ProductListEvent $event
     * @param string|null      $eventName
     *
     * @return ProductListEvent
     *
     * @throws \InvalidArgumentException when the given event is not supported or an event name is not provided
     */
    public function dispatch(object $event, string $eventName = null): object
    {
        if (!$event instanceof ProductListEvent) {
            throw new \InvalidArgumentException(sprintf(
                'Unexpected event type. Expected instance of %s.',
                ProductListEvent::class
            ));
        }
        if (!$eventName) {
            throw new \InvalidArgumentException('The event name must not be empty.');
        }

        parent::dispatch($event, $eventName);
        parent::dispatch($event, $eventName . '.' . $event->getProductListType());

        return $event;
    }
}
