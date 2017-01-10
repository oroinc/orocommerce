<?php

namespace Oro\Bundle\ShippingBundle\Method\Configuration\Composed;

use Symfony\Component\HttpFoundation\ParameterBag;

class ComposedShippingMethodConfiguration extends ParameterBag implements ComposedShippingMethodConfigurationInterface
{
    const FIELD_ALLOW_UNLISTED_SHIPPING_METHOD = 'is_allow_unlisted_shipping_method';
    const FIELD_IS_SHIPPING_METHOD_LOCKED = 'is_shipping_method_locked';
    const FIELD_IS_OVERRIDDEN_SHIPPING_COST =   'is_overridden_shipping_cost';
    const FIELD_SHIPPING_METHOD = 'shipping_method';
    const FIELD_SHIPPING_METHOD_TYPE = 'shipping_method_type';
    const FIELD_SHIPPING_COST = 'shipping_cost';

    /**
     * {@inheritdoc}
     */
    public function isAllowUnlistedShippingMethod()
    {
        return $this->get(self::FIELD_ALLOW_UNLISTED_SHIPPING_METHOD, false);
    }

    /**
     * {@inheritdoc}
     */
    public function isShippingMethodLocked()
    {
        return $this->get(self::FIELD_IS_SHIPPING_METHOD_LOCKED, false);
    }

    /**
     * {@inheritdoc}
     */
    public function isOverriddenShippingCost()
    {
        return $this->get(self::FIELD_IS_OVERRIDDEN_SHIPPING_COST, false);
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingMethod()
    {
        return $this->get(self::FIELD_SHIPPING_METHOD);
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingMethodType()
    {
        return $this->get(self::FIELD_SHIPPING_METHOD_TYPE);
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingCost()
    {
        return $this->get(self::FIELD_SHIPPING_COST);
    }
}
