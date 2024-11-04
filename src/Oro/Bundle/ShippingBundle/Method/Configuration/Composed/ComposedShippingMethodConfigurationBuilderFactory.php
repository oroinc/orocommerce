<?php

namespace Oro\Bundle\ShippingBundle\Method\Configuration\Composed;

class ComposedShippingMethodConfigurationBuilderFactory implements
    ComposedShippingMethodConfigurationBuilderFactoryInterface
{
    #[\Override]
    public function createBuilder()
    {
        return new ComposedShippingMethodConfigurationBuilder();
    }
}
