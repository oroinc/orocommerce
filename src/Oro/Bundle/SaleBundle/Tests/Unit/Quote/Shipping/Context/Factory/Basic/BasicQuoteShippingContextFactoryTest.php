<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\Context\Factory\Basic;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\SaleBundle\Quote\Calculable\CalculableQuoteInterface;
use Oro\Bundle\SaleBundle\Quote\Calculable\Factory\CalculableQuoteFactoryInterface;
use Oro\Bundle\SaleBundle\Quote\Shipping\Context\Factory\Basic\BasicQuoteShippingContextFactory;
use Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\QuoteToShippingLineItemConverterInterface;
use Oro\Bundle\ShippingBundle\Context\Builder\Factory\ShippingContextBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\Builder\ShippingContextBuilderInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class BasicQuoteShippingContextFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var BasicQuoteShippingContextFactory
     */
    private $basicQuoteShippingContextFactory;

    /**
     * @var ShippingContextBuilderFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shippingContextBuilderFactoryMock;

    /**
     * @var QuoteToShippingLineItemConverterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $quoteToShippingLineItemConverterMock;

    /**
     * @var TotalProcessorProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $totalProcessorProviderMock;

    /**
     * @var CalculableQuoteFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $calculableQuoteFactoryMock;

    public function setUp()
    {
        $this->shippingContextBuilderFactoryMock = $this
            ->getMockBuilder(ShippingContextBuilderFactoryInterface::class)
            ->getMock();

        $this->quoteToShippingLineItemConverterMock = $this
            ->getMockBuilder(QuoteToShippingLineItemConverterInterface::class)
            ->getMock();

        $this->totalProcessorProviderMock = $this
            ->getMockBuilder(TotalProcessorProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->calculableQuoteFactoryMock = $this
            ->getMockBuilder(CalculableQuoteFactoryInterface::class)
            ->getMock();

        $this->basicQuoteShippingContextFactory = new BasicQuoteShippingContextFactory(
            $this->shippingContextBuilderFactoryMock,
            $this->quoteToShippingLineItemConverterMock,
            $this->totalProcessorProviderMock,
            $this->calculableQuoteFactoryMock
        );
    }

    public function testCreate()
    {
        $quoteId = 5;
        $currency = 'USD';
        $amount = 20;
        $subTotal = Price::create($amount, $currency);

        $totalMock = $this->getTotalMock($amount, $currency);
        $calculableQuoteMock = $this->getCalculableQuoteMock();
        $shippingAddressMock = $this->getShippingAddressMock();
        $shippingLineItems = $this->createMock(DoctrineShippingLineItemCollection::class);
        $quoteMock = $this->getQuoteMock();
        $websiteMock = $this->createMock(Website::class);
        $shippingContextMock = $this->getShippingContextMock();
        $builder = $this->getShippingContextBuilderMock();

        $this->calculableQuoteFactoryMock
            ->expects($this->once())
            ->method('createCalculableQuote')
            ->with($shippingLineItems)
            ->willReturn($calculableQuoteMock);

        $this->totalProcessorProviderMock
            ->expects($this->once())
            ->method('getTotal')
            ->with($calculableQuoteMock)
            ->willReturn($totalMock);

        $this->quoteToShippingLineItemConverterMock
            ->expects($this->once())
            ->method('convertLineItems')
            ->with($quoteMock)
            ->willReturn($shippingLineItems);

        $quoteMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn($quoteId);

        $quoteMock
            ->expects($this->exactly(2))
            ->method('getShippingAddress')
            ->willReturn($shippingAddressMock);

        $quoteMock
            ->expects($this->once())
            ->method('getCurrency')
            ->willReturn($currency);

        $quoteMock
            ->expects($this->exactly(2))
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $builder
            ->expects($this->once())
            ->method('setShippingAddress')
            ->with($shippingAddressMock);

        $builder
            ->expects($this->once())
            ->method('getResult')
            ->willReturn($shippingContextMock);

        $builder
            ->expects($this->once())
            ->method('setLineItems')
            ->with($shippingLineItems)
            ->willReturnSelf();

        $builder
            ->expects($this->once())
            ->method('setSubTotal')
            ->with($subTotal)
            ->willReturnSelf();

        $builder
            ->expects($this->once())
            ->method('setCurrency')
            ->with($currency)
            ->willReturnSelf();

        $builder
            ->expects($this->once())
            ->method('setWebsite')
            ->with($websiteMock);

        $this->shippingContextBuilderFactoryMock
            ->expects($this->once())
            ->method('createShippingContextBuilder')
            ->with($quoteMock, $quoteId)
            ->willReturn($builder);

        $actualContext = $this->basicQuoteShippingContextFactory->create($quoteMock);

        $this->assertEquals($shippingContextMock, $actualContext);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testUnsupportedEntity()
    {
        $this->basicQuoteShippingContextFactory->create(new \stdClass());
    }

    /**
     * @param int    $amount
     * @param string $currency
     *
     * @return Subtotal|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getTotalMock($amount, $currency)
    {
        $totalMock = $this->createMock(Subtotal::class);

        $totalMock
            ->expects($this->once())
            ->method('getAmount')
            ->willReturn($amount);

        $totalMock
            ->expects($this->once())
            ->method('getCurrency')
            ->willReturn($currency);

        return $totalMock;
    }

    /**
     * @return CalculableQuoteInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getCalculableQuoteMock()
    {
        return $this->createMock(CalculableQuoteInterface::class);
    }

    /**
     * @return ShippingContextBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getShippingContextBuilderMock()
    {
        return $this->createMock(ShippingContextBuilderInterface::class);
    }

    /**
     * @return QuoteAddress|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getShippingAddressMock()
    {
        return $this->createMock(QuoteAddress::class);
    }

    /**
     * @return Quote|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getQuoteMock()
    {
        return $this->createMock(Quote::class);
    }

    /**
     * @return ShippingContext|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getShippingContextMock()
    {
        return $this->createMock(ShippingContext::class);
    }
}
