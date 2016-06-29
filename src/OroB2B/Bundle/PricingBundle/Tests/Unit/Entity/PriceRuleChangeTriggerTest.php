<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;

class PriceRuleChangeTriggerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;
    use EntityTestCaseTrait;

    public function testIdAccessor()
    {
        $trigger = new PriceRuleChangeTrigger(new PriceRule(), new Product());

        $this->assertPropertyAccessors($trigger, [
            ['id', 42]
        ]);
    }

    public function testAccessorsWithoutProduct()
    {
        /** @var PriceRule $priseRule */
        $priseRule = $this->getEntity(PriceRule::class, ['id' => 123]);

        $trigger = new PriceRuleChangeTrigger($priseRule);

        $this->assertSame($priseRule->getId(), $trigger->getPriceRule()->getId());
        $this->assertNull($trigger->getProduct());
    }

    public function testAccessorsWithProduct()
    {
        /** @var PriceRule $priseRule */
        $priseRule = $this->getEntity(PriceRule::class, ['id' => 123]);
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 123]);

        $trigger = new PriceRuleChangeTrigger($priseRule, $product);

        $this->assertSame($priseRule->getId(), $trigger->getPriceRule()->getId());
        $this->assertSame($product->getId(), $trigger->getProduct()->getId());
    }
}
