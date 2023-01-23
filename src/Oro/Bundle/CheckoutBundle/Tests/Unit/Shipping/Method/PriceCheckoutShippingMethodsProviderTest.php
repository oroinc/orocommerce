<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Shipping\Method;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\PriceCheckoutShippingMethodsProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;

class PriceCheckoutShippingMethodsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingPriceProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingPriceProvider;

    /** @var CheckoutShippingContextProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutShippingContextProvider;

    /** @var PriceCheckoutShippingMethodsProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->shippingPriceProvider = $this->createMock(ShippingPriceProvider::class);
        $this->checkoutShippingContextProvider = $this->createMock(CheckoutShippingContextProvider::class);

        $this->provider = new PriceCheckoutShippingMethodsProvider(
            $this->shippingPriceProvider,
            $this->checkoutShippingContextProvider
        );
    }

    public function testGetApplicableMethodsViews()
    {
        $checkout = $this->createMock(Checkout::class);
        $shippingContext = $this->createMock(ShippingContext::class);
        $expectedMethods = (new ShippingMethodViewCollection())
            ->addMethodView('flat_rate', ['identifier' => 'flat_rate']);

        $this->checkoutShippingContextProvider->expects($this->once())
            ->method('getContext')
            ->with($checkout)
            ->willReturn($shippingContext);

        $this->shippingPriceProvider->expects($this->once())
            ->method('getApplicableMethodsViews')
            ->with($shippingContext)
            ->willReturn($expectedMethods);

        $actualMethods = $this->provider->getApplicableMethodsViews($checkout);

        $this->assertEquals($expectedMethods, $actualMethods);
    }

    public function testGetPrice()
    {
        $shippingMethod = 'flat_rate';
        $shippingMethodType = 'primary';
        $price = Price::create(12, 'USD');
        $checkout = $this->createMock(Checkout::class);
        $shippingContext = $this->createMock(ShippingContext::class);

        $checkout->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn($shippingMethod);
        $checkout->expects($this->once())
            ->method('getShippingMethodType')
            ->willReturn($shippingMethodType);

        $this->checkoutShippingContextProvider->expects($this->once())
            ->method('getContext')
            ->with($checkout)
            ->willReturn($shippingContext);

        $this->shippingPriceProvider->expects($this->once())
            ->method('getPrice')
            ->with($shippingContext, $shippingMethod, $shippingMethodType)
            ->willReturn($price);

        $actualPrice = $this->provider->getPrice($checkout);

        $this->assertEquals($price, $actualPrice);
    }
}
