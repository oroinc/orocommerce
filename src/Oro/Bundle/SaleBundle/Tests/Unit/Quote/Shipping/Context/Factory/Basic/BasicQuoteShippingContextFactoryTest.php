<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\Context\Factory\Basic;

use Doctrine\Common\Collections\ArrayCollection;
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
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BasicQuoteShippingContextFactoryTest extends TestCase
{
    private ShippingContextBuilderFactoryInterface|MockObject $shippingContextBuilderFactory;

    private QuoteToShippingLineItemConverterInterface|MockObject $quoteToShippingLineItemConverter;

    private TotalProcessorProvider|MockObject $totalProcessorProvider;

    private CalculableQuoteFactoryInterface|MockObject $calculableQuoteFactory;

    private BasicQuoteShippingContextFactory $basicQuoteShippingContextFactory;

    #[\Override]
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

    public function testCreate(): void
    {
        $quoteId = 5;
        $currency = 'USD';
        $amount = 20.0;
        $subTotal = Price::create($amount, $currency);

        $total = $this->getTotal($amount, $currency);
        $calculableQuote = $this->createMock(CalculableQuoteInterface::class);
        $shippingAddress = new QuoteAddress();
        $shippingLineItems = new ArrayCollection([]);
        $customer = new Customer();
        $customerUser = new CustomerUser();
        $website = new Website();
        $shippingContext = $this->createMock(ShippingContext::class);
        $builder = $this->createMock(ShippingContextBuilderInterface::class);
        $quote = $this->createQuote($quoteId, $currency, $shippingAddress, $website, $customer, $customerUser);

        $this->calculableQuoteFactory->expects(self::once())
            ->method('createCalculableQuote')
            ->with($shippingLineItems)
            ->willReturn($calculableQuote);

        $this->totalProcessorProvider->expects(self::once())
            ->method('getTotal')
            ->with($calculableQuote)
            ->willReturn($total);

        $this->quoteToShippingLineItemConverter->expects(self::once())
            ->method('convertLineItems')
            ->with($quote)
            ->willReturn($shippingLineItems);

        $builder->expects(self::once())
            ->method('setShippingAddress')
            ->with($shippingAddress);
        $builder->expects(self::once())
            ->method('getResult')
            ->willReturn($shippingContext);
        $builder->expects(self::once())
            ->method('setLineItems')
            ->with($shippingLineItems)
            ->willReturnSelf();
        $builder->expects(self::once())
            ->method('setSubTotal')
            ->with($subTotal)
            ->willReturnSelf();
        $builder->expects(self::once())
            ->method('setCurrency')
            ->with($currency)
            ->willReturnSelf();
        $builder->expects(self::once())
            ->method('setWebsite')
            ->with($website);
        $builder->expects(self::once())
            ->method('setCustomer')
            ->with($customer);
        $builder->expects(self::once())
            ->method('setCustomerUser')
            ->with($customerUser);

        $this->shippingContextBuilderFactory->expects(self::once())
            ->method('createShippingContextBuilder')
            ->with($quote, $quoteId)
            ->willReturn($builder);

        $actualContext = $this->basicQuoteShippingContextFactory->create($quote);

        self::assertEquals($shippingContext, $actualContext);
    }

    public function testUnsupportedEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->basicQuoteShippingContextFactory->create(new \stdClass());
    }

    private function getTotal(float $amount, string $currency): Subtotal
    {
        $total = $this->createMock(Subtotal::class);
        $total->expects(self::once())
            ->method('getAmount')
            ->willReturn($amount);
        $total->expects(self::once())
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
