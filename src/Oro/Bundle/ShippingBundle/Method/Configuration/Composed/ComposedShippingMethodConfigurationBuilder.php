<?php

namespace Oro\Bundle\ShippingBundle\Method\Configuration\Composed;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Method\Configuration\AllowUnlistedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Method\Configuration\MethodLockedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Method\Configuration\OverriddenCostShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Method\Configuration\PreConfiguredShippingMethodConfigurationInterface;

class ComposedShippingMethodConfigurationBuilder implements ComposedShippingMethodConfigurationBuilderInterface
{
    /**
     * @var string
     */
    private $shippingMethod;

    /**
     * @var string
     */
    private $shippingMethodType;

    /**
     * @var Price
     */
    private $shippingCost;

    /**
     * @var bool
     */
    private $isOverriddenCost;

    /**
     * @var bool
     */
    private $isShippingMethodLocked;

    /**
     * @var bool
     */
    private $isAllowUnlistedShippingMethod;

    /**
     * {@inheritdoc}
     */
    public function getResult()
    {
        $params = [];

        if (null !== $this->shippingMethod) {
            $params[ComposedShippingMethodConfiguration::FIELD_SHIPPING_METHOD] = $this->shippingMethod;
        }

        if (null !== $this->shippingMethodType) {
            $params[ComposedShippingMethodConfiguration::FIELD_SHIPPING_METHOD_TYPE] = $this->shippingMethodType;
        }

        if (null !== $this->shippingCost) {
            $params[ComposedShippingMethodConfiguration::FIELD_SHIPPING_COST] = $this->shippingCost;
        }

        if (null !== $this->isOverriddenCost) {
            $params[ComposedShippingMethodConfiguration::FIELD_IS_OVERRIDDEN_SHIPPING_COST] = $this->isOverriddenCost;
        }

        if (null !== $this->isShippingMethodLocked) {
            $params[ComposedShippingMethodConfiguration::FIELD_IS_SHIPPING_METHOD_LOCKED] =
                $this->isShippingMethodLocked;
        }

        if (null !== $this->isAllowUnlistedShippingMethod) {
            $params[ComposedShippingMethodConfiguration::FIELD_ALLOW_UNLISTED_SHIPPING_METHOD] =
                $this->isAllowUnlistedShippingMethod;
        }

        return new ComposedShippingMethodConfiguration($params);
    }

    /**
     * {@inheritdoc}
     */
    public function buildShippingMethod(
        PreConfiguredShippingMethodConfigurationInterface $preConfiguredShippingMethodConfiguration
    ) {
        $this->shippingMethod = $preConfiguredShippingMethodConfiguration->getShippingMethod();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function buildShippingMethodType(
        PreConfiguredShippingMethodConfigurationInterface $preConfiguredShippingMethodConfiguration
    ) {
        $this->shippingMethodType = $preConfiguredShippingMethodConfiguration->getShippingMethodType();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function buildShippingCost(
        PreConfiguredShippingMethodConfigurationInterface $preConfiguredShippingMethodConfiguration
    ) {
        $this->shippingCost = $preConfiguredShippingMethodConfiguration->getShippingCost();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function buildIsOverriddenCost(
        OverriddenCostShippingMethodConfigurationInterface $overriddenCostShippingMethodConfiguration
    ) {
        $this->isOverriddenCost = $overriddenCostShippingMethodConfiguration->isOverriddenShippingCost();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function buildIsShippingMethodLocked(
        MethodLockedShippingMethodConfigurationInterface $methodLockedShippingMethodConfiguration
    ) {
        $this->isShippingMethodLocked = $methodLockedShippingMethodConfiguration->isShippingMethodLocked();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function buildIsAllowUnlistedShippingMethod(
        AllowUnlistedShippingMethodConfigurationInterface $allowUnlistedShippingMethodConfiguration
    ) {
        $this->isAllowUnlistedShippingMethod = $allowUnlistedShippingMethodConfiguration
            ->isAllowUnlistedShippingMethod();

        return $this;
    }
}
