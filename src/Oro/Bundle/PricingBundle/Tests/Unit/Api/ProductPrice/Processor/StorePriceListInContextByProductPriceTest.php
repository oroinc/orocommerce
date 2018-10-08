<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\ProductPrice\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\StorePriceListInContextByProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;

class StorePriceListInContextByProductPriceTest extends FormProcessorTestCase
{
    /** @var StorePriceListInContextByProductPrice */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new StorePriceListInContextByProductPrice();
    }

    public function testProcessNoPriceList()
    {
        $this->context->setResult(new ProductPrice());
        $this->processor->process($this->context);
        self::assertFalse($this->context->has('price_list_id'));
    }

    public function testProcessNoPriceListId()
    {
        $productPrice = new ProductPrice();
        $productPrice->setPriceList(new PriceList());

        $this->context->setResult($productPrice);
        $this->processor->process($this->context);
        self::assertFalse($this->context->has('price_list_id'));
    }

    public function testProcess()
    {
        $priceListId = 23;

        $priceList = $this->createMock(PriceList::class);
        $priceList->expects(self::any())
            ->method('getId')
            ->willReturn($priceListId);

        $productPrice = new ProductPrice();
        $productPrice->setPriceList($priceList);

        $this->context->setResult($productPrice);
        $this->processor->process($this->context);
        self::assertSame($priceListId, $this->context->get('price_list_id'));
    }
}
