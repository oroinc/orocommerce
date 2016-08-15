<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\ShippingBundle\Entity\FlatRateRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;

class FlatRateRuleConfigurationTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $entity = new FlatRateRuleConfiguration();
        $properties = [
            ['value', 1.0],
            ['handlingFeeValue', 2.0],
            ['processingType', FlatRateRuleConfiguration::PROCESSING_TYPE_PER_ITEM],
        ];

        $this->assertPropertyAccessors($entity, $properties);
    }

    public function testToString()
    {
        $entity = new FlatRateRuleConfiguration();
        $entity->setValue(42);
        $entity->setMethod('UPS');
        $entity->setType('TEST');
        $entity->setRule((new ShippingRule())->setCurrency('USD'));
        $this->assertEquals('UPS, 42 USD', (string)$entity);
    }
}
