<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\Context\Factory\Basic;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
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
use Oro\Component\Testing\Unit\EntityTrait;

class BasicQuoteShippingContextFactoryTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

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

    protected function setUp(): void
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
        $amount = 20.0;
        $subTotal = Price::create($amount, $currency);

        $totalMock = $this->getTotalMock($amount, $currency);
        $calculableQuoteMock = $this->getCalculableQuoteMock();
        $shippingAddressMock = new QuoteAddress();
        $shippingLineItems = $this->createMock(DoctrineShippingLineItemCollection::class);
        $customer = new Customer();
        $customerUser = new CustomerUser();
        $website = new Website();
        $shippingContextMock = $this->getShippingContextMock();
        $builder = $this->getShippingContextBuilderMock();
        $quote = $this->createQuote($quoteId, $currency, $shippingAddressMock, $website, $customer, $customerUser);

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
            ->with($quote)
            ->willReturn($shippingLineItems);

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
            ->with($website);

        $builder
            ->expects($this->once())
            ->method('setCustomer')
            ->with($customer);

        $builder
            ->expects($this->once())
            ->method('setCustomerUser')
            ->with($customerUser);

        $this->shippingContextBuilderFactoryMock
            ->expects($this->once())
            ->method('createShippingContextBuilder')
            ->with($quote, $quoteId)
            ->willReturn($builder);

        $actualContext = $this->basicQuoteShippingContextFactory->create($quote);

        $this->assertEquals($shippingContextMock, $actualContext);
    }

    public function testUnsupportedEntity()
    {
        $this->expectException(\InvalidArgumentException::class);
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
     * @return ShippingContext|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getShippingContextMock()
    {
        return $this->createMock(ShippingContext::class);
    }

    private function createQuote(
        int $quoteId,
        string $currency,
        QuoteAddress $shippingAddressMock,
        Website $website,
        Customer $customer,
        CustomerUser $customerUser
    ): Quote {
        /** @var Quote $quote */
        $quote = $this->getEntity(Quote::class, ['id' => $quoteId]);

        $quote
            ->setShippingAddress($shippingAddressMock)
            ->setCurrency($currency)
            ->setWebsite($website)
            ->setCustomer($customer)
            ->setCustomerUser($customerUser);

        return $quote;
    }
}
