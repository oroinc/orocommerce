<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Entity;

use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodTypeConfig;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ShippingRuleMethodTypeConfigTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $properties = [
            ['id', 1],
            ['type', 'custom'],
            ['options', ['custom' => 'test']],
            ['enabled', true],
            ['methodConfig', new ShippingRuleMethodConfig()],
        ];

        $entity = new ShippingRuleMethodTypeConfig();

        $this->assertPropertyAccessors($entity, $properties);
    }
}
