<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\ProductPrice\Processor;

use Oro\Bundle\PricingBundle\Api\ProductPrice\PriceListIDContextStorageInterface;
use Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\StorePriceListInContextByProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Component\ChainProcessor\ContextInterface;
use PHPUnit\Framework\TestCase;

class StorePriceListInContextByProductPriceTest extends TestCase
{
    /**
     * @var PriceListIDContextStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $priceListIDContextStorage;

    /**
     * @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $context;

    /**
     * @var StorePriceListInContextByProductPrice
     */
    private $processor;

    protected function setUp()
    {
        $this->priceListIDContextStorage = $this->createMock(PriceListIDContextStorageInterface::class);
        $this->context = $this->createMock(ContextInterface::class);

        $this->processor = new StorePriceListInContextByProductPrice(
            $this->priceListIDContextStorage
        );
    }

    public function testProcessWrongType()
    {
        $this->processor->process($this->context);
    }

    public function testProcessNoPriceList()
    {
        $this->context
            ->expects(static::once())
            ->method('getResult')
            ->willReturn(new ProductPrice());

        $this->priceListIDContextStorage
            ->expects(static::never())
            ->method('store');

        $this->processor->process($this->context);
    }

    public function testProcessNoPriceListId()
    {
        $productPrice = new ProductPrice();
        $productPrice->setPriceList(new PriceList());

        $this->context
            ->expects(static::once())
            ->method('getResult')
            ->willReturn($productPrice);

        $this->priceListIDContextStorage
            ->expects(static::never())
            ->method('store');

        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $priceListId = 23;

        $priceList = $this->createMock(PriceList::class);
        $priceList
            ->expects(static::exactly(2))
            ->method('getId')
            ->willReturn($priceListId);

        $productPrice = new ProductPrice();
        $productPrice->setPriceList($priceList);

        $this->context
            ->expects(static::once())
            ->method('getResult')
            ->willReturn($productPrice);

        $this->priceListIDContextStorage
            ->expects(static::once())
            ->method('store')
            ->with($priceListId, $this->context);

        $this->processor->process($this->context);
    }
}
