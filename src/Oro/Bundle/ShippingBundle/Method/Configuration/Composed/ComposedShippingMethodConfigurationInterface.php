<?php

namespace Oro\Bundle\ShippingBundle\Method\Configuration\Composed;

use Oro\Bundle\ShippingBundle\Method\Configuration\AllowUnlistedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Method\Configuration\MethodLockedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Method\Configuration\OverriddenCostShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Method\Configuration\PreConfiguredShippingMethodConfigurationInterface;

/**
 * Defines the contract for composed shipping method configurations.
 *
 * This interface combines multiple configuration aspects including pre-configured shipping method,
 * unlisted method allowance, cost overrides, and method locking, providing a comprehensive configuration interface
 * for complex shipping scenarios.
 */
interface ComposedShippingMethodConfigurationInterface extends
    PreConfiguredShippingMethodConfigurationInterface,
    AllowUnlistedShippingMethodConfigurationInterface,
    OverriddenCostShippingMethodConfigurationInterface,
    MethodLockedShippingMethodConfigurationInterface
{
}
