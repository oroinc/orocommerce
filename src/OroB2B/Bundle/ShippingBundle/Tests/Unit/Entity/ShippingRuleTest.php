<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingDestination;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;

class ShippingRulesTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '123'],
            ['name', 'Test Rule'],
            ['status', ShippingRule::STATUS_ENABLED, ShippingRule::STATUS_DISABLED],
            ['sortOrder', 10],
            ['conditions',  'Subtotal > 50 USD AND Subtotal <= 100 USD'],
        ];

        $this->assertPropertyAccessors(new ShippingRule(), $properties);
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
        $status = ShippingRule::STATUS_ENABLED;
        $sortOrder = 20;
        $conditions = 'Subtotal > 50 USD AND Subtotal <= 100 USD';
        
        /** @var ShippingRule $shippingRule */
        $shippingRule = $this->getEntity(
            'OroB2B\Bundle\ShippingBundle\Entity\ShippingRule',
            [
                'id' => $id,
                'name' => $name,
                'status' => $status,
                'sortOrder' => $sortOrder,
                'conditions' => $conditions,
            ]
        );

        $this->assertEquals($id, $shippingRule->getId());
        $this->assertEquals($name, $shippingRule->getName());
        $this->assertEquals($status, $shippingRule->getStatus());
        $this->assertEquals($sortOrder, $shippingRule->getSortOrder());
        $this->assertEquals($conditions, $shippingRule->getConditions());

        $shippingRuleCopy = clone $shippingRule;

        $this->assertNull($shippingRuleCopy->getId());
        $this->assertEquals($name, $shippingRuleCopy->getName());
        $this->assertEquals($status, $shippingRuleCopy->getStatus());
        $this->assertEquals($sortOrder, $shippingRuleCopy->getSortOrder());
        $this->assertEquals($conditions, $shippingRuleCopy->getConditions());
    }
}
