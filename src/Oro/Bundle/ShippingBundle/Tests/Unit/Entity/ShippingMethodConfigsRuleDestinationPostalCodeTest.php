<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Entity;

use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfigsRuleDestination;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfigsRuleDestinationPostalCode;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class ShippingMethodConfigsRuleDestinationPostalCodeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '123'],
            ['name', 'wewfe'],
            ['destination', new ShippingMethodConfigsRuleDestination()],
        ];

        $rule = new ShippingMethodConfigsRuleDestinationPostalCode();
        static::assertPropertyAccessors($rule, $properties);
    }
}
