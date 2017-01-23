<?php

namespace Oro\Bundle\ShippingBundle\Method\Event;

interface MethodTypeRemovalEventDispatcherInterface
{
    /**
     * @param int|string $methodId
     * @param int|string $typeId
     * @return void
     */
    public function dispatch($methodId, $typeId);
}
