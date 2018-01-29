<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model\DTO;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListTrigger;

class PriceRuleTriggerTest extends \PHPUnit_Framework_TestCase
{
    private const PRODUCT_ID = 42;

    /**
     * @var PriceList|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceList;

    /**
     * @var PriceListTrigger
     */
    protected $priceRuleTrigger;

    protected function setUp()
    {
        $this->priceList = $this->createMock(PriceList::class);
        $this->priceRuleTrigger = new PriceListTrigger($this->priceList, [self::PRODUCT_ID]);
    }

    public function testGetPriceList()
    {
        $this->assertSame($this->priceList, $this->priceRuleTrigger->getPriceList());
    }

    public function testGetProduct()
    {
        $this->assertSame([self::PRODUCT_ID], $this->priceRuleTrigger->getProducts());
    }
}
