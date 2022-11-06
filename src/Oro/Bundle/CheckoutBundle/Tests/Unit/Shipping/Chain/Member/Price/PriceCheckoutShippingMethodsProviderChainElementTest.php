<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Shipping\Chain\Member\Price;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member\Price\PriceCheckoutShippingMethodsProviderChainElement;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;

class PriceCheckoutShippingMethodsProviderChainElementTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingPriceProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingPriceProvider;

    /** @var CheckoutShippingContextProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutShippingContextProvider;

    /** @var PriceCheckoutShippingMethodsProviderChainElement */
    private $testedMethodsProvider;

    protected function setUp(): void
    {
        $this->shippingPriceProvider = $this->createMock(ShippingPriceProvider::class);
        $this->checkoutShippingContextProvider = $this->createMock(CheckoutShippingContextProvider::class);

        $this->testedMethodsProvider = new PriceCheckoutShippingMethodsProviderChainElement(
            $this->shippingPriceProvider,
            $this->checkoutShippingContextProvider
        );
    }

    public function testGetApplicableMethodsViewsWithoutSuccessor()
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

        $actualMethods = $this->testedMethodsProvider->getApplicableMethodsViews($checkout);

        $this->assertEquals($expectedMethods, $actualMethods);
    }

    public function testGetApplicableMethodsViewsWithSuccessor()
    {
        $checkout = $this->createMock(Checkout::class);
        $successor = $this->createMock(CheckoutShippingMethodsProviderInterface::class);
        $expectedMethods = (new ShippingMethodViewCollection())
            ->addMethodView('flat_rate', ['identifier' => 'flat_rate'])
            ->addMethodTypeView('flat_rate', 'flat_rate_1', ['identifier' => 'flat_rate_1']);

        $this->checkoutShippingContextProvider->expects($this->never())
            ->method('getContext');

        $this->shippingPriceProvider->expects($this->never())
            ->method('getApplicableMethodsViews');

        $successor->expects($this->once())
            ->method('getApplicableMethodsViews')
            ->with($checkout)
            ->willReturn($expectedMethods);

        $this->testedMethodsProvider->setSuccessor($successor);
        $actualMethods = $this->testedMethodsProvider->getApplicableMethodsViews($checkout);

        $this->assertEquals($expectedMethods, $actualMethods);
    }

    public function testGetPriceWithoutSuccessor()
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

        $actualPrice = $this->testedMethodsProvider->getPrice($checkout);

        $this->assertEquals($price, $actualPrice);
    }

    public function testGetPriceWithSuccessor()
    {
        $price = Price::create(12, 'USD');
        $checkout = $this->createMock(Checkout::class);
        $successor = $this->createMock(CheckoutShippingMethodsProviderInterface::class);

        $checkout->expects($this->never())
            ->method('getShippingMethod');
        $checkout->expects($this->never())
            ->method('getShippingMethodType');

        $this->checkoutShippingContextProvider->expects($this->never())
            ->method('getContext');

        $this->shippingPriceProvider->expects($this->never())
            ->method('getPrice');

        $successor->expects($this->once())
            ->method('getPrice')
            ->with($checkout)
            ->willReturn($price);

        $this->testedMethodsProvider->setSuccessor($successor);
        $actualPrice = $this->testedMethodsProvider->getPrice($checkout);

        $this->assertEquals($price, $actualPrice);
    }
}
