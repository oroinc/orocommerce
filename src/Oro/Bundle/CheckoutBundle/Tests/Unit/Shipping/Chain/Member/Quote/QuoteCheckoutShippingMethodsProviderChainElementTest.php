<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Shipping\Chain\Member\Quote;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member\Quote\QuoteCheckoutShippingMethodsProviderChainElement;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Quote\Shipping\Configuration\QuoteShippingConfigurationFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\ShippingConfiguredPriceProviderInterface;

class QuoteCheckoutShippingMethodsProviderChainElementTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutShippingContextProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutShippingContextProvider;

    /** @var ShippingConfiguredPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingConfiguredPriceProvider;

    /** @var QuoteShippingConfigurationFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $quoteShippingConfigurationFactory;

    /** @var QuoteCheckoutShippingMethodsProviderChainElement */
    private $testedMethodsProvider;

    protected function setUp(): void
    {
        $this->checkoutShippingContextProvider = $this->createMock(CheckoutShippingContextProvider::class);
        $this->shippingConfiguredPriceProvider = $this->createMock(ShippingConfiguredPriceProviderInterface::class);
        $this->quoteShippingConfigurationFactory = $this->createMock(QuoteShippingConfigurationFactory::class);

        $this->testedMethodsProvider = new QuoteCheckoutShippingMethodsProviderChainElement(
            $this->checkoutShippingContextProvider,
            $this->shippingConfiguredPriceProvider,
            $this->quoteShippingConfigurationFactory
        );
    }

    public function testGetApplicableMethodsViews()
    {
        $quote = $this->createMock(Quote::class);
        $quoteDemand = $this->createMock(QuoteDemand::class);
        $checkout = $this->createMock(Checkout::class);
        $shippingContext = $this->createMock(ShippingContext::class);
        $configuration = $this->createMock(ComposedShippingMethodConfigurationInterface::class);
        $expectedMethods = (new ShippingMethodViewCollection())
            ->addMethodView('flat_rate', ['identifier' => 'flat_rate']);

        $quoteDemand->expects($this->once())
            ->method('getQuote')
            ->willReturn($quote);

        $checkout->expects($this->once())
            ->method('getSourceEntity')
            ->willReturn($quoteDemand);

        $this->checkoutShippingContextProvider->expects($this->once())
            ->method('getContext')
            ->with($checkout)
            ->willReturn($shippingContext);

        $this->quoteShippingConfigurationFactory->expects($this->once())
            ->method('createQuoteShippingConfig')
            ->with($quote)
            ->willReturn($configuration);

        $this->shippingConfiguredPriceProvider->expects($this->once())
            ->method('getApplicableMethodsViews')
            ->with($configuration, $shippingContext)
            ->willReturn($expectedMethods);

        $actualMethods = $this->testedMethodsProvider->getApplicableMethodsViews($checkout);

        $this->assertEquals($expectedMethods, $actualMethods);
    }

    public function testGetApplicableMethodsViewsWhenSourceEntityNotQuote()
    {
        $quoteDemand = $this->createMock(QuoteDemand::class);
        $checkout = $this->createMock(Checkout::class);
        $expectedMethods = (new ShippingMethodViewCollection());

        $checkout->expects($this->once())
            ->method('getSourceEntity')
            ->willReturn(new \stdClass());

        $quoteDemand->expects($this->never())
            ->method('getQuote');

        $this->checkoutShippingContextProvider->expects($this->never())
            ->method('getContext');

        $this->quoteShippingConfigurationFactory->expects($this->never())
            ->method('createQuoteShippingConfig');

        $this->shippingConfiguredPriceProvider->expects($this->never())
            ->method('getApplicableMethodsViews');

        $actualMethods = $this->testedMethodsProvider->getApplicableMethodsViews($checkout);

        $this->assertEquals($expectedMethods, $actualMethods);
    }

    public function testGetApplicableMethodsViewsWithSuccessorNotEmpty()
    {
        $quoteDemand = $this->createMock(QuoteDemand::class);
        $checkout = $this->createMock(Checkout::class);
        $successor = $this->createMock(CheckoutShippingMethodsProviderInterface::class);
        $expectedMethods = (new ShippingMethodViewCollection())
            ->addMethodView('flat_rate', ['identifier' => 'flat_rate'])
            ->addMethodTypeView('flat_rate', 'flat_rate_1', ['identifier' => 'flat_rate_1']);

        $checkout->expects($this->never())
            ->method('getSourceEntity');

        $quoteDemand->expects($this->never())
            ->method('getQuote');

        $this->checkoutShippingContextProvider->expects($this->never())
            ->method('getContext');

        $this->quoteShippingConfigurationFactory->expects($this->never())
            ->method('createQuoteShippingConfig');

        $this->shippingConfiguredPriceProvider->expects($this->never())
            ->method('getApplicableMethodsViews');

        $successor->expects($this->once())
            ->method('getApplicableMethodsViews')
            ->with($checkout)
            ->willReturn($expectedMethods);

        $this->testedMethodsProvider->setSuccessor($successor);
        $actualMethods = $this->testedMethodsProvider->getApplicableMethodsViews($checkout);

        $this->assertEquals($expectedMethods, $actualMethods);
    }

    public function testGetPrice()
    {
        $price = Price::create(12, 'USD');
        $shippingMethodId = 'shippingMethodId';
        $shippingMethodTypeId = 'shippingMethodTypeId';
        $quote = $this->createMock(Quote::class);
        $quoteDemand = $this->createMock(QuoteDemand::class);
        $checkout = $this->createMock(Checkout::class);
        $shippingContext = $this->createMock(ShippingContext::class);
        $configuration = $this->createMock(ComposedShippingMethodConfigurationInterface::class);

        $checkout->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn($shippingMethodId);
        $checkout->expects($this->once())
            ->method('getShippingMethodType')
            ->willReturn($shippingMethodTypeId);
        $checkout->expects($this->once())
            ->method('getSourceEntity')
            ->willReturn($quoteDemand);

        $quoteDemand->expects($this->once())
            ->method('getQuote')
            ->willReturn($quote);

        $this->checkoutShippingContextProvider->expects($this->once())
            ->method('getContext')
            ->with($checkout)
            ->willReturn($shippingContext);

        $this->quoteShippingConfigurationFactory->expects($this->once())
            ->method('createQuoteShippingConfig')
            ->with($quote)
            ->willReturn($configuration);

        $this->shippingConfiguredPriceProvider->expects($this->once())
            ->method('getPrice')
            ->with($shippingMethodId, $shippingMethodTypeId, $configuration, $shippingContext)
            ->willReturn($price);

        $actualPrice = $this->testedMethodsProvider->getPrice($checkout);

        $this->assertEquals($price, $actualPrice);
    }

    public function testGetPriceWhenSourceEntityNotQuote()
    {
        $price = null;
        $quoteDemand = $this->createMock(QuoteDemand::class);
        $checkout = $this->createMock(Checkout::class);

        $checkout->expects($this->never())
            ->method('getShippingMethod');
        $checkout->expects($this->never())
            ->method('getShippingMethodType');
        $checkout->expects($this->once())
            ->method('getSourceEntity')
            ->willReturn(new \stdClass());

        $quoteDemand->expects($this->never())
            ->method('getQuote');

        $this->checkoutShippingContextProvider->expects($this->never())
            ->method('getContext');

        $this->quoteShippingConfigurationFactory->expects($this->never())
            ->method('createQuoteShippingConfig');

        $this->shippingConfiguredPriceProvider->expects($this->never())
            ->method('getPrice');

        $actualPrice = $this->testedMethodsProvider->getPrice($checkout);

        $this->assertEquals($price, $actualPrice);
    }

    public function testGetPriceWithSuccessor()
    {
        $price = Price::create(12, 'USD');
        $checkout = $this->createMock(Checkout::class);
        $successor = $this->createMock(CheckoutShippingMethodsProviderInterface::class);

        $successor->expects($this->once())
            ->method('getPrice')
            ->with($checkout)
            ->willReturn($price);

        $this->testedMethodsProvider->setSuccessor($successor);
        $actualPrice = $this->testedMethodsProvider->getPrice($checkout);

        $this->assertEquals($price, $actualPrice);
    }
}
