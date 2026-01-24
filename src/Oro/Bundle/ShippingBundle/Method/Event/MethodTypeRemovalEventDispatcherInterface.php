<?php

namespace Oro\Bundle\ShippingBundle\Method\Event;

/**
 * Defines the contract for dispatchers that handle shipping method type removal events.
 *
 * Implementations of this interface are responsible for dispatching {@see MethodTypeRemovalEvent} instances
 * when shipping method types are removed from the system.
 */
interface MethodTypeRemovalEventDispatcherInterface
{
    /**
     * @param int|string $methodId
     * @param int|string $typeId
     * @return void
     */
    public function dispatch($methodId, $typeId);
}
