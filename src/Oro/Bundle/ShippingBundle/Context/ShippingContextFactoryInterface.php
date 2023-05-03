<?php

namespace Oro\Bundle\ShippingBundle\Context;

/**
 * Represents a factory to create a shipping context based on a specific entity.
 */
interface ShippingContextFactoryInterface
{
    public function create(object $entity): ShippingContextInterface;
}
