<?php

namespace Oro\Bundle\ShippingBundle\Method\Configuration\Composed;

/**
 * Creates instances of composed shipping method configuration builders.
 *
 * This factory provides a simple way to instantiate {@see ComposedShippingMethodConfigurationBuilder} instances
 * for building complex shipping method configurations.
 */
class ComposedShippingMethodConfigurationBuilderFactory implements
    ComposedShippingMethodConfigurationBuilderFactoryInterface
{
    #[\Override]
    public function createBuilder()
    {
        return new ComposedShippingMethodConfigurationBuilder();
    }
}
