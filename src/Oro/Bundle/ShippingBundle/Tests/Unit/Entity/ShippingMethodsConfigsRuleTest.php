<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRuleDestination;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class ShippingMethodsConfigsRuleTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '123'],
            ['rule', new Rule()],
            ['currency', 'USD'],
            ['organization', new Organization()]
        ];

        $rule = new ShippingMethodsConfigsRule();
        static::assertPropertyAccessors($rule, $properties);
        static::assertPropertyCollection($rule, 'methodConfigs', new ShippingMethodConfig());
        static::assertPropertyCollection($rule, 'destinations', new ShippingMethodsConfigsRuleDestination());
        static::assertPropertyCollection($rule, 'websites', new Website());
    }
}
