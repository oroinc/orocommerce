<?php

namespace Oro\Bundle\ShippingBundle\Method\Event;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Dispatches shipping method renaming events.
 *
 * This dispatcher creates and dispatches {@see MethodRenamingEvent} instances when shipping method identifiers
 * are changed, allowing listeners to update references and configurations.
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
