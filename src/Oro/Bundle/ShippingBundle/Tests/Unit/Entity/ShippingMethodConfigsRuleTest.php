<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Entity;

use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfigsRule;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfigsRuleDestination;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;

class ShippingMethodConfigsRuleTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '123'],
            ['rule', new Rule()],
            ['currency', 'USD'],
        ];

        $rule = new ShippingMethodConfigsRule();
        static::assertPropertyAccessors($rule, $properties);
        static::assertPropertyCollection($rule, 'methodConfigs', new ShippingMethodConfig());
        static::assertPropertyCollection($rule, 'destinations', new ShippingMethodConfigsRuleDestination());
    }
}
