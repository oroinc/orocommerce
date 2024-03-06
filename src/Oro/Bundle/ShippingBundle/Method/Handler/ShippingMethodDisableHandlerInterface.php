<?php

namespace Oro\Bundle\ShippingBundle\Method\Handler;

/**
 * Represents a service that handles shipping rules when an integration is disabled.
 */
interface ShippingMethodDisableHandlerInterface
{
    public function handleMethodDisable(string $methodId): void;
}
