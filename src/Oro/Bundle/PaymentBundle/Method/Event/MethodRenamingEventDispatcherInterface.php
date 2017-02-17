<?php

namespace Oro\Bundle\PaymentBundle\Method\Event;

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
