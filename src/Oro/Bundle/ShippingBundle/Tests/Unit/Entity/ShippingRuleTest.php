<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleDestination;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodConfig;

class ShippingRuleTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '123'],
            ['name', 'Test Rule'],
            ['enabled', true],
            ['priority', 10],
            ['conditions', 'Subtotal > 50 USD AND Subtotal <= 100 USD'],
            ['currency', 'USD'],
            ['stopProcessing', 'USD'],
        ];

        $rule = new ShippingRule();
        $this->assertPropertyAccessors($rule, $properties);
        $this->assertPropertyCollection($rule, 'methodConfigs', new ShippingRuleMethodConfig());
        $this->assertPropertyCollection($rule, 'destinations', new ShippingRuleDestination());
    }

    public function testAddMethodConfig()
    {
        $config = new ShippingRuleMethodConfig();
        $shippingRule = new ShippingRule();
        $this->assertEmpty($shippingRule->getMethodConfigs());
        $shippingRule->addMethodConfig($config);
        $result = $shippingRule->getMethodConfigs();
        $this->assertCount(1, $result);
        $this->assertEquals($config->getRule()->getId(), $shippingRule->getId());
    }

    public function testAddDestination()
    {
        $destination = new ShippingRuleDestination();
        $shippingRule = new ShippingRule();
        $this->assertEmpty($shippingRule->getDestinations());
        $shippingRule->addDestination($destination);
        $result = $shippingRule->getDestinations();
        $this->assertCount(1, $result);
        $this->assertEquals($destination->getRule()->getId(), $shippingRule->getId());
    }

    public function testToString()
    {
        $shippingRule = new ShippingRule();
        $this->assertSame('', (string)$shippingRule);

        $shippingRule->setName('Test Name');
        $this->assertSame('Test Name', (string)$shippingRule);
    }

    public function testClone()
    {
        $id = 123;
        $name = 'Test Rule';
        $stopProcessing = false;
        $enabled = true;
        $priority = 20;
        $conditions = 'Subtotal > 50 USD AND Subtotal <= 100 USD';
        $currency = 'USD';

        /** @var ShippingRule $shippingRule */
        $shippingRule = $this->getEntity(
            'Oro\Bundle\ShippingBundle\Entity\ShippingRule',
            [
                'id' => $id,
                'name' => $name,
                'stopProcessing' => $stopProcessing,
                'enabled' => $enabled,
                'priority' => $priority,
                'conditions' => $conditions,
                'currency' => $currency,
            ]
        );

        $this->assertEquals($id, $shippingRule->getId());
        $this->assertEquals($name, $shippingRule->getName());
        $this->assertEquals($stopProcessing, $shippingRule->isStopProcessing());
        $this->assertEquals($enabled, $shippingRule->isEnabled());
        $this->assertEquals($priority, $shippingRule->getPriority());
        $this->assertEquals($conditions, $shippingRule->getConditions());
        $this->assertEquals($currency, $shippingRule->getCurrency());

        $shippingRuleCopy = clone $shippingRule;

        $this->assertNull($shippingRuleCopy->getId());
        $this->assertEquals($name, $shippingRuleCopy->getName());
        $this->assertEquals($stopProcessing, $shippingRuleCopy->isStopProcessing());
        $this->assertEquals($enabled, $shippingRuleCopy->isEnabled());
        $this->assertEquals($priority, $shippingRuleCopy->getPriority());
        $this->assertEquals($conditions, $shippingRuleCopy->getConditions());
        $this->assertEquals($currency, $shippingRuleCopy->getCurrency());
    }
}
