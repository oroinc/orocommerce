<?php

namespace Oro\Bundle\ShippingBundle\Method\Event;

interface MethodRemovalEventDispatcherInterface
{
    /**
     * @param int|string $id
     * @return void
     */
    public function dispatch($id);
}
