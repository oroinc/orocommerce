<?php

namespace Oro\Bundle\ShippingBundle\Method\Configuration;

/**
 * Defines the contract for configurations with overridden shipping costs.
 *
 * This interface extends pre-configured shipping method configuration to indicate whether the shipping cost
 * has been manually overridden, allowing custom pricing to take precedence over calculated shipping rates.
 */
interface OverriddenCostShippingMethodConfigurationInterface extends PreConfiguredShippingMethodConfigurationInterface
{
    /**
     * @return bool
     */
    public function isOverriddenShippingCost();
}
