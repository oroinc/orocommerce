<?php

namespace Oro\Bundle\ShippingBundle\Method\Event;

/**
 * Defines the contract for dispatchers that handle shipping method renaming events.
 *
 * Implementations of this interface are responsible for dispatching {@see MethodRenamingEvent} instances
 * when shipping method identifiers are changed.
 */
interface MethodRenamingEventDispatcherInterface
{
    /**
     * @param string $oldId
     * @param string $newId
     *
     * @return void
     */
    public function dispatch($oldId, $newId);
}
