<?php

namespace Oro\Bundle\ShippingBundle\Method\Configuration;

/**
 * Defines the contract for configurations with locked shipping methods.
 *
 * This interface extends pre-configured shipping method configuration to indicate whether the shipping method selection
 * is locked, preventing customers from changing the shipping method during checkout.
 */
interface MethodLockedShippingMethodConfigurationInterface extends PreConfiguredShippingMethodConfigurationInterface
{
    /**
     * @return bool
     */
    public function isShippingMethodLocked();
}
