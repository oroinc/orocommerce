<?php

namespace Oro\Bundle\ShippingBundle\Method\Configuration\Composed;

/**
 * Defines the contract for factories that create composed shipping method configuration builders.
 *
 * Implementations of this interface provide a way to instantiate
 * {@see ComposedShippingMethodConfigurationBuilderInterface} instances
 * for building complex shipping method configurations.
 */
interface ComposedShippingMethodConfigurationBuilderFactoryInterface
{
    /**
     * @return ComposedShippingMethodConfigurationBuilderInterface
     */
    public function createBuilder();
}
