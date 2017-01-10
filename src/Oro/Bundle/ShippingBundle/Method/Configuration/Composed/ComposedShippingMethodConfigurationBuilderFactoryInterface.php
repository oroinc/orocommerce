<?php

namespace Oro\Bundle\ShippingBundle\Method\Configuration\Composed;

interface ComposedShippingMethodConfigurationBuilderFactoryInterface
{
    /**
     * @return ComposedShippingMethodConfigurationBuilderInterface
     */
    public function createBuilder();
}
