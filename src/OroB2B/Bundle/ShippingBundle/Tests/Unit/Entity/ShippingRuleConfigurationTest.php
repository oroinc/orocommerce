<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Tests\Unit\Entity\Stub\CustomShippingRuleConfiguration;

class ShippingRuleConfigurationTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /**
     * @var ShippingRuleConfiguration $entity
     */
    protected $entity;

    public function setUp()
    {
        $this->entity = new CustomShippingRuleConfiguration();
    }

    public function tearDown()
    {
        unset($this->entity);
    }

    public function testAccessors()
    {
        $properties = [
            ['id', 1],
            ['type', '123'],
            ['rule', new ShippingRule()],
            ['enabled', true],
        ];

        $this->assertPropertyAccessors($this->entity, $properties);
    }
}
