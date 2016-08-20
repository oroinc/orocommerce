<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;

class PriceRuleChangeTriggerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;
    use EntityTestCaseTrait;

    public function testIdAccessor()
    {
        $trigger = new PriceRuleChangeTrigger(new PriceList(), new Product());

        $this->assertPropertyAccessors($trigger, [
            ['id', 42]
        ]);
    }

    public function testAccessorsWithoutProduct()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 123]);

        $trigger = new PriceRuleChangeTrigger($priceList);

        $this->assertSame($priceList->getId(), $trigger->getPriceList()->getId());
        $this->assertNull($trigger->getProduct());
    }

    public function testAccessorsWithProduct()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 123]);
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 123]);

        $trigger = new PriceRuleChangeTrigger($priceList, $product);

        $this->assertSame($priceList->getId(), $trigger->getPriceList()->getId());
        $this->assertSame($product->getId(), $trigger->getProduct()->getId());
    }
}
