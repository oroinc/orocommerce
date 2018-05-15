<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model\DTO;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListTrigger;
use Oro\Component\Testing\Unit\EntityTrait;

class PriceRuleTriggerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    private const PRICE_LIST_ID = 1001;
    private const PRODUCT_ID = 2002;

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
        $this->priceList = $this->getEntity(PriceList::class, ['id' => self::PRICE_LIST_ID]);
        $this->priceRuleTrigger = new PriceListTrigger($this->priceList, [self::PRODUCT_ID]);
    }

    public function testGetPriceList()
    {
        $this->assertSame($this->priceList, $this->priceRuleTrigger->getPriceList());
    }

    public function testGetProduct()
    {
        $this->assertSame([self::PRICE_LIST_ID => [self::PRODUCT_ID]], $this->priceRuleTrigger->getProducts());
    }
}
