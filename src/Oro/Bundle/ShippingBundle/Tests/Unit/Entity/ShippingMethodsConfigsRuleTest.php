<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Entity;

use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRuleDestination;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;

class ShippingMethodsConfigsRuleTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
            ['rule', new Rule()],
            ['currency', 'USD'],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
        ];

        $rule = new ShippingMethodsConfigsRule();
        static::assertPropertyAccessors($rule, $properties);
        static::assertPropertyCollection($rule, 'methodConfigs', new ShippingMethodConfig());
        static::assertPropertyCollection($rule, 'destinations', new ShippingMethodsConfigsRuleDestination());
    }

    public function testPrePersist()
    {
        $entity = new ShippingMethodsConfigsRule();
        $this->assertNull($entity->getCreatedAt());
        $this->assertNull($entity->getUpdatedAt());

        $entity->prePersist();
        $this->assertInstanceOf('\DateTime', $entity->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $entity->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $entity = new ShippingMethodsConfigsRule();
        $this->assertNull($entity->getUpdatedAt());

        $entity->preUpdate();
        $this->assertInstanceOf('\DateTime', $entity->getUpdatedAt());
    }
}
