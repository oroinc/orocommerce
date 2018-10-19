<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Configuration\Composed;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfiguration;

class ComposedShippingMethodConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters()
    {
        $shippingCost = Price::create(12, 'USD');
        $shippingMethod = 'someMethodId';
        $shippingMethodType = 'shippingMethodTypeId';
        $isAllowedUnlisted = true;
        $isLocked = true;
        $isOverridden = true;

        $configuration = new ComposedShippingMethodConfiguration(
            [
                ComposedShippingMethodConfiguration::FIELD_SHIPPING_COST => $shippingCost,
                ComposedShippingMethodConfiguration::FIELD_SHIPPING_METHOD => $shippingMethod,
                ComposedShippingMethodConfiguration::FIELD_SHIPPING_METHOD_TYPE => $shippingMethodType,
                ComposedShippingMethodConfiguration::FIELD_ALLOW_UNLISTED_SHIPPING_METHOD => $isAllowedUnlisted,
                ComposedShippingMethodConfiguration::FIELD_IS_SHIPPING_METHOD_LOCKED => $isLocked,
                ComposedShippingMethodConfiguration::FIELD_IS_OVERRIDDEN_SHIPPING_COST => $isOverridden,
            ]
        );

        $this->assertEquals($configuration->getShippingCost(), $shippingCost);
        $this->assertEquals($configuration->getShippingMethod(), $shippingMethod);
        $this->assertEquals($configuration->getShippingMethodType(), $shippingMethodType);
        $this->assertEquals($configuration->isAllowUnlistedShippingMethod(), $isAllowedUnlisted);
        $this->assertEquals($configuration->isShippingMethodLocked(), $isLocked);
        $this->assertEquals($configuration->isOverriddenShippingCost(), $isOverridden);
    }

    public function testGettersWhenUnset()
    {
        $shippingCost = null;
        $shippingMethod = null;
        $shippingMethodType = null;
        $isAllowedUnlisted = false;
        $isLocked = false;
        $isOverridden = false;

        $configuration = new ComposedShippingMethodConfiguration([]);

        $this->assertEquals($configuration->getShippingCost(), $shippingCost);
        $this->assertEquals($configuration->getShippingMethod(), $shippingMethod);
        $this->assertEquals($configuration->getShippingMethodType(), $shippingMethodType);
        $this->assertEquals($configuration->isAllowUnlistedShippingMethod(), $isAllowedUnlisted);
        $this->assertEquals($configuration->isShippingMethodLocked(), $isLocked);
        $this->assertEquals($configuration->isOverriddenShippingCost(), $isOverridden);
    }
}
