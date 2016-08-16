<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Model\DTO;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Model\DTO\PriceRuleTrigger;
use OroB2B\Bundle\ProductBundle\Entity\Product;

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
     * @var PriceRuleTrigger
     */
    protected $priceRuleTrigger;

    protected function setUp()
    {
        $this->priceList = $this->getMock(PriceList::class);
        $this->product = $this->getMock(Product::class);
        $this->priceRuleTrigger = new PriceRuleTrigger($this->priceList, $this->product);
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
