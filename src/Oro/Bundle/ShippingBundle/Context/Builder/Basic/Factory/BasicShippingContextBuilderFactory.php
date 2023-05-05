<?php

namespace Oro\Bundle\ShippingBundle\Context\Builder\Basic\Factory;

use Oro\Bundle\ShippingBundle\Context\Builder\Basic\BasicShippingContextBuilder;
use Oro\Bundle\ShippingBundle\Context\Builder\Factory\ShippingContextBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\Builder\ShippingContextBuilderInterface;

/**
 * The factory to create a basic shipping context builder.
 */
class BasicShippingContextBuilderFactory implements ShippingContextBuilderFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createShippingContextBuilder(
        object $sourceEntity,
        mixed $sourceEntityId
    ): ShippingContextBuilderInterface {
        return new BasicShippingContextBuilder($sourceEntity, $sourceEntityId);
    }
}
