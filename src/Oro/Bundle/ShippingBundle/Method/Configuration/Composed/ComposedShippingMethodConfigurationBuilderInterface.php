<?php

namespace Oro\Bundle\ShippingBundle\Method\Configuration\Composed;

use Oro\Bundle\ShippingBundle\Method\Configuration\AllowUnlistedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Method\Configuration\MethodLockedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Method\Configuration\OverriddenCostShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Method\Configuration\PreConfiguredShippingMethodConfigurationInterface;

/**
 * Defines the contract for builders that create composed shipping method configurations.
 *
 * Implementations of this interface provide a fluent API for building
 * {@see ComposedShippingMethodConfigurationInterface} instances by aggregating configuration data from various sources
 * including pre-configured methods, cost overrides, method locking, and unlisted method allowance settings.
 */
interface ComposedShippingMethodConfigurationBuilderInterface
{
    /**
     * @return ComposedShippingMethodConfigurationInterface
     */
    public function getResult();

    /**
     * @param PreConfiguredShippingMethodConfigurationInterface $preConfiguredShippingMethodConfiguration
     *
     * @return self
     */
    public function buildShippingMethod(
        PreConfiguredShippingMethodConfigurationInterface $preConfiguredShippingMethodConfiguration
    );

    /**
     * @param PreConfiguredShippingMethodConfigurationInterface $preConfiguredShippingMethodConfiguration
     *
     * @return self
     */
    public function buildShippingMethodType(
        PreConfiguredShippingMethodConfigurationInterface $preConfiguredShippingMethodConfiguration
    );

    /**
     * @param PreConfiguredShippingMethodConfigurationInterface $preConfiguredShippingMethodConfiguration
     *
     * @return self
     */
    public function buildShippingCost(
        PreConfiguredShippingMethodConfigurationInterface $preConfiguredShippingMethodConfiguration
    );

    /**
     * @param OverriddenCostShippingMethodConfigurationInterface $overriddenCostShippingMethodConfiguration
     *
     * @return self
     */
    public function buildIsOverriddenCost(
        OverriddenCostShippingMethodConfigurationInterface $overriddenCostShippingMethodConfiguration
    );

    /**
     * @param MethodLockedShippingMethodConfigurationInterface $methodLockedShippingMethodConfiguration
     *
     * @return self
     */
    public function buildIsShippingMethodLocked(
        MethodLockedShippingMethodConfigurationInterface $methodLockedShippingMethodConfiguration
    );

    /**
     * @param AllowUnlistedShippingMethodConfigurationInterface $allowUnlistedShippingMethodConfiguration
     *
     * @return self
     */
    public function buildIsAllowUnlistedShippingMethod(
        AllowUnlistedShippingMethodConfigurationInterface $allowUnlistedShippingMethodConfiguration
    );
}
