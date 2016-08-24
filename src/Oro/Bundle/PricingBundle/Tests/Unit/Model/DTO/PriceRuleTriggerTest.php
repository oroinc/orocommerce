<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model\DTO;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListTrigger;
use Oro\Bundle\ProductBundle\Entity\Product;

class PriceRuleTriggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PriceList|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceList;

    /**
     * @var Product|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $product;

    /**
     * @var PriceListTrigger
     */
    protected $priceRuleTrigger;

    protected function setUp()
    {
        $this->priceList = $this->getMock(PriceList::class);
        $this->product = $this->getMock(Product::class);
        $this->priceRuleTrigger = new PriceListTrigger($this->priceList, $this->product);
    }

    public function testGetPriceList()
    {
        $this->assertSame($this->priceList, $this->priceRuleTrigger->getPriceList());
    }

    public function testGetProduct()
    {
        $this->assertSame($this->product, $this->priceRuleTrigger->getProduct());
    }
}
