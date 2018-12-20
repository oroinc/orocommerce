<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Configuration\Composed;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfiguration;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfigurationBuilder;

class ComposedShippingMethodConfigurationBuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testAllSetters()
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

        $composedShippingMethodConfigurationBuilder = new ComposedShippingMethodConfigurationBuilder();

        $actualConfiguration = $composedShippingMethodConfigurationBuilder
            ->buildIsAllowUnlistedShippingMethod($configuration)
            ->buildShippingMethodType($configuration)
            ->buildShippingMethod($configuration)
            ->buildShippingCost($configuration)
            ->buildIsShippingMethodLocked($configuration)
            ->buildIsOverriddenCost($configuration)
            ->getResult();

        $this->assertEquals($configuration, $actualConfiguration);
    }
}
