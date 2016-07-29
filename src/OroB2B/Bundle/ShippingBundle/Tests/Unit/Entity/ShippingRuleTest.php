<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleDestination;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;
use OroB2B\Bundle\ShippingBundle\Tests\Unit\Entity\Stub\CustomShippingRuleConfiguration;

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
        $this->assertPropertyCollection($rule, 'configurations', new CustomShippingRuleConfiguration());
    }

    public function testLineItemsSetter()
    {
        $configurations = new ArrayCollection([new CustomShippingRuleConfiguration()]);
        $shippingRule = new ShippingRule();
        $this->assertEmpty($shippingRule->getConfigurations());
        $shippingRule->setConfigurations($configurations);
        $result = $shippingRule->getConfigurations();
        $this->assertEquals($configurations, $result);
        foreach ($result as $configuration) {
            $this->assertEquals($configuration->getRule()->getId(), $shippingRule->getId());
        }
    }

    public function testSetCurrency()
    {
        $config = new CustomShippingRuleConfiguration();
        $this->assertNull($config->getCurrency());
        $configurations = new ArrayCollection([$config]);
        $shippingRule = new ShippingRule();
        $shippingRule->setConfigurations($configurations);
        $shippingRule->setCurrency('USD');
        foreach ($shippingRule->getConfigurations() as $configuration) {
            $this->assertEquals('USD', $configuration->getCurrency());
        }
    }

    public function testRelations()
    {
        $this->assertPropertyCollections(new ShippingRule(), [
            ['destinations', new ShippingRuleDestination()],
        ]);
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
            'OroB2B\Bundle\ShippingBundle\Entity\ShippingRule',
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
