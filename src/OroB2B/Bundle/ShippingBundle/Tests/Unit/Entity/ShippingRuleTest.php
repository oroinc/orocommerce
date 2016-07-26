<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingDestination;
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
            ['nameHash', sha1('Test Rule')],
            ['enabled', true],
            ['priority', 10],
            ['conditions', 'Subtotal > 50 USD AND Subtotal <= 100 USD'],
            ['currency', 'USD'],
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

    public function testRelations()
    {
        $this->assertPropertyCollections(new ShippingRule(), [
            ['shippingDestinations', new ShippingDestination()],
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
        $nameHash = sha1('Test Rule');
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
                'nameHash' => $nameHash,
                'enabled' => $enabled,
                'priority' => $priority,
                'conditions' => $conditions,
                'currency' => $currency,
            ]
        );

        $this->assertEquals($id, $shippingRule->getId());
        $this->assertEquals($name, $shippingRule->getName());
        $this->assertEquals($nameHash, $shippingRule->getNameHash());
        $this->assertEquals($enabled, $shippingRule->isEnabled());
        $this->assertEquals($priority, $shippingRule->getPriority());
        $this->assertEquals($conditions, $shippingRule->getConditions());
        $this->assertEquals($currency, $shippingRule->getCurrency());

        $shippingRuleCopy = clone $shippingRule;

        $this->assertNull($shippingRuleCopy->getId());
        $this->assertEquals($name, $shippingRuleCopy->getName());
        $this->assertEquals($nameHash, $shippingRuleCopy->getNameHash());
        $this->assertEquals($enabled, $shippingRuleCopy->isEnabled());
        $this->assertEquals($priority, $shippingRuleCopy->getPriority());
        $this->assertEquals($conditions, $shippingRuleCopy->getConditions());
        $this->assertEquals($currency, $shippingRuleCopy->getCurrency());
    }
}
