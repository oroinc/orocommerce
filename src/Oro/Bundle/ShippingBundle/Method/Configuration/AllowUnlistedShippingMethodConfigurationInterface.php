<?php

namespace Oro\Bundle\ShippingBundle\Method\Configuration;

/**
 * Defines the contract for configurations that allow unlisted shipping methods.
 *
 * This interface extends pre-configured shipping method configuration to indicate whether shipping methods
 * not explicitly listed in the configuration should be allowed, enabling flexible shipping method selection
 * in checkout processes.
 */
interface AllowUnlistedShippingMethodConfigurationInterface extends PreConfiguredShippingMethodConfigurationInterface
{
    /**
     * @return bool
     */
    public function isAllowUnlistedShippingMethod();
}
