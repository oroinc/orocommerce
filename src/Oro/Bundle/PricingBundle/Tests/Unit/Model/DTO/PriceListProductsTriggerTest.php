<?php

namespace Oro\Bundle\PricingBundle\Tests\unit\Model\DTO;

use Oro\Bundle\PricingBundle\Model\DTO\PriceListProductsTrigger;

class PriceListProductsTriggerTest extends \PHPUnit_Framework_TestCase
{
    private const PRODUCT_ID = 42;

    /** @var PriceListProductsTrigger */
    protected $priceRuleTrigger;

    protected function setUp()
    {
        $this->priceRuleTrigger = new PriceListProductsTrigger([self::PRODUCT_ID]);
    }

    public function testGetPriceList()
    {
        $this->assertNull($this->priceRuleTrigger->getPriceList());
    }

    public function testGetProduct()
    {
        $this->assertSame([self::PRODUCT_ID], $this->priceRuleTrigger->getProducts());
    }
}
