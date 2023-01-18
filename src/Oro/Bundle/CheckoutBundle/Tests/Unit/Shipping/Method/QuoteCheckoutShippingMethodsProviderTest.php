<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Shipping\Method;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\QuoteCheckoutShippingMethodsProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Quote\Shipping\Configuration\QuoteShippingConfigurationFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\ShippingConfiguredPriceProviderInterface;

class QuoteCheckoutShippingMethodsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutShippingContextProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutShippingContextProvider;

    /** @var ShippingConfiguredPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingConfiguredPriceProvider;

    /** @var QuoteShippingConfigurationFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $quoteShippingConfigurationFactory;

    /** @var QuoteCheckoutShippingMethodsProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->checkoutShippingContextProvider = $this->createMock(CheckoutShippingContextProvider::class);
        $this->shippingConfiguredPriceProvider = $this->createMock(ShippingConfiguredPriceProviderInterface::class);
        $this->quoteShippingConfigurationFactory = $this->createMock(QuoteShippingConfigurationFactory::class);

        $this->provider = new QuoteCheckoutShippingMethodsProvider(
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

        $actualMethods = $this->provider->getApplicableMethodsViews($checkout);

        $this->assertEquals($expectedMethods, $actualMethods);
    }

    public function testGetApplicableMethodsViewsWhenSourceEntityNotQuote()
    {
        $quoteDemand = $this->createMock(QuoteDemand::class);
        $checkout = $this->createMock(Checkout::class);
        $expectedMethods = new ShippingMethodViewCollection();

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

        $actualMethods = $this->provider->getApplicableMethodsViews($checkout);

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

        $actualPrice = $this->provider->getPrice($checkout);

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

        $actualPrice = $this->provider->getPrice($checkout);

        $this->assertEquals($price, $actualPrice);
    }
}
