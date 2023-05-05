<?php

namespace Oro\Bundle\ShippingBundle\Context\Builder\Factory;

use Oro\Bundle\ShippingBundle\Context\Builder\ShippingContextBuilderInterface;

/**
 * Represents a factory to create a shipping context builder.
 */
interface ShippingContextBuilderFactoryInterface
{
    public function createShippingContextBuilder(
        object $sourceEntity,
        mixed $sourceEntityId
    ): ShippingContextBuilderInterface;
}
