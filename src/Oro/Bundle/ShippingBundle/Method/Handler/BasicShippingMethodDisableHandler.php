<?php

namespace Oro\Bundle\ShippingBundle\Method\Handler;

/**
 * Handles shipping rules when an integration is disabled.
 */
class BasicShippingMethodDisableHandler implements ShippingMethodDisableHandlerInterface
{
    #[\Override]
    public function handleMethodDisable(string $methodId): void
    {
    }
}
