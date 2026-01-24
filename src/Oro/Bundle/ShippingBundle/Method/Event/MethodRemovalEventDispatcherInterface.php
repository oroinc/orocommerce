<?php

namespace Oro\Bundle\ShippingBundle\Method\Event;

/**
 * Defines the contract for dispatchers that handle shipping method removal events.
 *
 * Implementations of this interface are responsible for dispatching {@see MethodRemovalEvent} instances
 * when shipping methods are removed from the system.
 */
interface MethodRemovalEventDispatcherInterface
{
    /**
     * @param int|string $id
     * @return void
     */
    public function dispatch($id);
}
