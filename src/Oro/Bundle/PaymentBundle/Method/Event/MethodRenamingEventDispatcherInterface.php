<?php

namespace Oro\Bundle\PaymentBundle\Method\Event;

/**
 * Defines the contract for dispatching payment method renaming events.
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
