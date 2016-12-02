<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Model;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Factory\QuoteShippingContextFactory;
use Oro\Bundle\SaleBundle\Model\QuoteShippingCostRecalculator;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;

class QuoteShippingCostRecalculatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QuoteShippingContextFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingContextFactoryMock;

    /**
     * @var ShippingPriceProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingPriceProvider;

    /**
     * @var Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteMock;

    /**
     * @var ShippingContext|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingContextMock;

    /**
     * @var Price|\PHPUnit_Framework_MockObject_MockObject
     */
    private $priceMock;

    protected function setUp()
    {
        $this->shippingContextFactoryMock = $this
            ->getMockBuilder(QuoteShippingContextFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingContextMock = $this->getMock(ShippingContextInterface::class);

        $this->shippingPriceProvider = $this
            ->getMockBuilder(ShippingPriceProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteMock = $this
            ->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceMock = $this
            ->getMockBuilder(Price::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function test()
    {
        $shippingMethod = 'someShippingMethod';
        $shippingMethodType = 'someShippingMethodType';
        $priceValue = 10;

        $this->quoteMock
            ->expects($this->once())
            ->method('getOverriddenShippingCostAmount')
            ->willReturn(null);

        $this->quoteMock
            ->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn($shippingMethod);

        $this->quoteMock
            ->expects($this->once())
            ->method('getShippingMethodType')
            ->willReturn($shippingMethodType);

        $this->shippingContextFactoryMock
            ->expects($this->once())
            ->method('create')
            ->with($this->quoteMock)
            ->willReturn($this->shippingContextMock);

        $this->priceMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn($priceValue);

        $this->shippingPriceProvider
            ->expects($this->once())
            ->method('getPrice')
            ->with($this->shippingContextMock, $shippingMethod, $shippingMethodType)
            ->willReturn($this->priceMock);

        $this->quoteMock
            ->expects($this->once())
            ->method('setEstimatedShippingCostAmount')
            ->with($priceValue);

        $recalculator = new QuoteShippingCostRecalculator($this->shippingContextFactoryMock);
        $recalculator->setShippingPriceProvider($this->shippingPriceProvider);

        $recalculator->recalculateQuoteShippingCost($this->quoteMock);
    }

    public function testWithOverriddenShippingCost()
    {
        $someOverriddenAmount = 10;

        $this->quoteMock
            ->expects($this->once())
            ->method('getOverriddenShippingCostAmount')
            ->willReturn($someOverriddenAmount);

        $this->quoteMock
            ->expects($this->never())
            ->method('getShippingMethod');

        $this->quoteMock
            ->expects($this->never())
            ->method('getShippingMethodType');

        $this->shippingContextFactoryMock
            ->expects($this->never())
            ->method('create');

        $this->priceMock
            ->expects($this->never())
            ->method('getValue');

        $this->shippingPriceProvider
            ->expects($this->never())
            ->method('getPrice');

        $this->quoteMock
            ->expects($this->never())
            ->method('setEstimatedShippingCostAmount');

        $recalculator = new QuoteShippingCostRecalculator($this->shippingContextFactoryMock);
        $recalculator->setShippingPriceProvider($this->shippingPriceProvider);

        $recalculator->recalculateQuoteShippingCost($this->quoteMock);
    }

    public function testWithoutShippingPriceProvider()
    {
        $this->quoteMock
            ->expects($this->never())
            ->method('getOverriddenShippingCostAmount');

        $this->quoteMock
            ->expects($this->never())
            ->method('getShippingMethod');

        $this->quoteMock
            ->expects($this->never())
            ->method('getShippingMethodType');

        $this->shippingContextFactoryMock
            ->expects($this->never())
            ->method('create');

        $this->priceMock
            ->expects($this->never())
            ->method('getValue');

        $this->shippingPriceProvider
            ->expects($this->never())
            ->method('getPrice');

        $this->quoteMock
            ->expects($this->never())
            ->method('setEstimatedShippingCostAmount');

        $recalculator = new QuoteShippingCostRecalculator($this->shippingContextFactoryMock);

        $recalculator->recalculateQuoteShippingCost($this->quoteMock);
    }
}
