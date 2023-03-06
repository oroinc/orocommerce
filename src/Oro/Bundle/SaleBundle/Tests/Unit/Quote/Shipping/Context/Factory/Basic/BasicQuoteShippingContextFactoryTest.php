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
use Oro\Component\Testing\ReflectionUtil;

class BasicQuoteShippingContextFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingContextBuilderFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingContextBuilderFactory;

    /** @var QuoteToShippingLineItemConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $quoteToShippingLineItemConverter;

    /** @var TotalProcessorProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $totalProcessorProvider;

    /** @var CalculableQuoteFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $calculableQuoteFactory;

    /** @var BasicQuoteShippingContextFactory */
    private $basicQuoteShippingContextFactory;

    protected function setUp(): void
    {
        $this->shippingContextBuilderFactory = $this->createMock(ShippingContextBuilderFactoryInterface::class);
        $this->quoteToShippingLineItemConverter = $this->createMock(QuoteToShippingLineItemConverterInterface::class);
        $this->totalProcessorProvider = $this->createMock(TotalProcessorProvider::class);
        $this->calculableQuoteFactory = $this->createMock(CalculableQuoteFactoryInterface::class);

        $this->basicQuoteShippingContextFactory = new BasicQuoteShippingContextFactory(
            $this->shippingContextBuilderFactory,
            $this->quoteToShippingLineItemConverter,
            $this->totalProcessorProvider,
            $this->calculableQuoteFactory
        );
    }

    public function testCreate()
    {
        $quoteId = 5;
        $currency = 'USD';
        $amount = 20.0;
        $subTotal = Price::create($amount, $currency);

        $total = $this->getTotal($amount, $currency);
        $calculableQuote = $this->createMock(CalculableQuoteInterface::class);
        $shippingAddress = new QuoteAddress();
        $shippingLineItems = $this->createMock(DoctrineShippingLineItemCollection::class);
        $customer = new Customer();
        $customerUser = new CustomerUser();
        $website = new Website();
        $shippingContext = $this->createMock(ShippingContext::class);
        $builder = $this->createMock(ShippingContextBuilderInterface::class);
        $quote = $this->createQuote($quoteId, $currency, $shippingAddress, $website, $customer, $customerUser);

        $this->calculableQuoteFactory->expects($this->once())
            ->method('createCalculableQuote')
            ->with($shippingLineItems)
            ->willReturn($calculableQuote);

        $this->totalProcessorProvider->expects($this->once())
            ->method('getTotal')
            ->with($calculableQuote)
            ->willReturn($total);

        $this->quoteToShippingLineItemConverter->expects($this->once())
            ->method('convertLineItems')
            ->with($quote)
            ->willReturn($shippingLineItems);

        $builder->expects($this->once())
            ->method('setShippingAddress')
            ->with($shippingAddress);
        $builder->expects($this->once())
            ->method('getResult')
            ->willReturn($shippingContext);
        $builder->expects($this->once())
            ->method('setLineItems')
            ->with($shippingLineItems)
            ->willReturnSelf();
        $builder->expects($this->once())
            ->method('setSubTotal')
            ->with($subTotal)
            ->willReturnSelf();
        $builder->expects($this->once())
            ->method('setCurrency')
            ->with($currency)
            ->willReturnSelf();
        $builder->expects($this->once())
            ->method('setWebsite')
            ->with($website);
        $builder->expects($this->once())
            ->method('setCustomer')
            ->with($customer);
        $builder->expects($this->once())
            ->method('setCustomerUser')
            ->with($customerUser);

        $this->shippingContextBuilderFactory->expects($this->once())
            ->method('createShippingContextBuilder')
            ->with($quote, $quoteId)
            ->willReturn($builder);

        $actualContext = $this->basicQuoteShippingContextFactory->create($quote);

        $this->assertEquals($shippingContext, $actualContext);
    }

    public function testUnsupportedEntity()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->basicQuoteShippingContextFactory->create(new \stdClass());
    }

    private function getTotal(float $amount, string $currency): Subtotal
    {
        $total = $this->createMock(Subtotal::class);
        $total->expects($this->once())
            ->method('getAmount')
            ->willReturn($amount);
        $total->expects($this->once())
            ->method('getCurrency')
            ->willReturn($currency);

        return $total;
    }

    private function createQuote(
        int $quoteId,
        string $currency,
        QuoteAddress $shippingAddress,
        Website $website,
        Customer $customer,
        CustomerUser $customerUser
    ): Quote {
        $quote = new Quote();
        ReflectionUtil::setId($quote, $quoteId);
        $quote->setShippingAddress($shippingAddress);
        $quote->setCurrency($currency);
        $quote->setWebsite($website);
        $quote->setCustomer($customer);
        $quote->setCustomerUser($customerUser);

        return $quote;
    }
}
