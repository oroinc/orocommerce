<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\Context\Factory\Basic;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Quote\Shipping\Context\Factory\Basic\BasicQuoteShippingContextFactory;
use Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\QuoteToShippingLineItemConverterInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Factory\ShippingContextFactory;
use Doctrine\Common\Collections\ArrayCollection;

class BasicQuoteShippingContextFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShippingContextFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingContextFactoryMock;

    /**
     * @var QuoteToShippingLineItemConverterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteToShippingLineItemConverterMock;

    /**
     * @var BasicQuoteShippingContextFactory
     */
    private $basicQuoteShippingContextFactory;

    public function setUp()
    {
        $this->shippingContextFactoryMock = $this
            ->getMockBuilder(ShippingContextFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteToShippingLineItemConverterMock = $this
            ->getMockBuilder(QuoteToShippingLineItemConverterInterface::class)
            ->getMock();

        $this->basicQuoteShippingContextFactory = new BasicQuoteShippingContextFactory(
            $this->shippingContextFactoryMock,
            $this->quoteToShippingLineItemConverterMock
        );
    }

    public function testCreate()
    {
        $quoteId = 5;
        $currency = 'USD';

        $shippingAddressMock = $this->getShippingAddressMock();
        $quoteDemands = new ArrayCollection([
            $this->getQuoteDemandMock()
        ]);
        $shippingLineItems = [
            $this->getShippingLineItemMock()
        ];

        $quoteMock = $this->getQuoteMock();

        $quoteMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn($quoteId);

        $quoteMock
            ->expects($this->once())
            ->method('getShippingAddress')
            ->willReturn($shippingAddressMock);

        $quoteMock
            ->expects($this->once())
            ->method('getCurrency')
            ->willReturn($currency);

        $quoteMock
            ->expects($this->once())
            ->method('getDemands')
            ->willReturn($quoteDemands);

        $shippingContextMock = $this->getShippingContextMock();

        $shippingContextMock
            ->expects($this->once())
            ->method('setShippingAddress')
            ->with($shippingAddressMock);

        $shippingContextMock
            ->expects($this->once())
            ->method('setCurrency')
            ->with($currency);

        $shippingContextMock
            ->expects($this->once())
            ->method('setSourceEntity')
            ->with($quoteMock);

        $shippingContextMock
            ->expects($this->once())
            ->method('setSourceEntityIdentifier')
            ->with($quoteId);

        $this->shippingContextFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($shippingContextMock);

        $shippingContextMock
            ->expects($this->once())
            ->method('setLineItemsByData')
            ->with($shippingLineItems);

        $this->quoteToShippingLineItemConverterMock
            ->expects($this->once())
            ->method('convertLineItems')
            ->with($quoteMock)
            ->willReturn($shippingLineItems);

        $actualContext = $this->basicQuoteShippingContextFactory->create($quoteMock);

        $this->assertEquals($shippingContextMock, $actualContext);
    }

    public function testCreateWithoutDemands()
    {
        $quoteId = 5;
        $currency = 'USD';

        $shippingAddressMock = $this->getShippingAddressMock();
        $quoteDemands = new ArrayCollection([]);

        $quoteMock = $this->getQuoteMock();

        $quoteMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn($quoteId);

        $quoteMock
            ->expects($this->once())
            ->method('getShippingAddress')
            ->willReturn($shippingAddressMock);

        $quoteMock
            ->expects($this->once())
            ->method('getCurrency')
            ->willReturn($currency);

        $quoteMock
            ->expects($this->once())
            ->method('getDemands')
            ->willReturn($quoteDemands);

        $shippingContextMock = $this->getShippingContextMock();

        $shippingContextMock
            ->expects($this->once())
            ->method('setShippingAddress')
            ->with($shippingAddressMock);

        $shippingContextMock
            ->expects($this->once())
            ->method('setCurrency')
            ->with($currency);

        $shippingContextMock
            ->expects($this->once())
            ->method('setSourceEntity')
            ->with($quoteMock);

        $shippingContextMock
            ->expects($this->once())
            ->method('setSourceEntityIdentifier')
            ->with($quoteId);

        $this->shippingContextFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($shippingContextMock);

        $shippingContextMock
            ->expects($this->never())
            ->method('setLineItemsByData');

        $this->quoteToShippingLineItemConverterMock
            ->expects($this->never())
            ->method('convertLineItems');

        $actualContext = $this->basicQuoteShippingContextFactory->create($quoteMock);

        $this->assertEquals($shippingContextMock, $actualContext);
    }

    /**
     * @return ShippingLineItemInterface[]|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getShippingLineItemMock()
    {
        return $this
            ->getMockBuilder(ShippingLineItemInterface::class)
            ->getMock();
    }

    /**
     * @return QuoteDemand|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getQuoteDemandMock()
    {
        return $this
            ->getMockBuilder(QuoteDemand::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return QuoteAddress|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getShippingAddressMock()
    {
        return $this
            ->getMockBuilder(QuoteAddress::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getQuoteMock()
    {
        return $this
            ->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return ShippingContext|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getShippingContextMock()
    {
        return $this
            ->getMockBuilder(ShippingContext::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
