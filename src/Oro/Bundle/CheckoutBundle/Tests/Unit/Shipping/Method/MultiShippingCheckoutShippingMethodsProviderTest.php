<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Shipping\Method;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\DefaultMultipleShippingMethodProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\MultiShippingCheckoutShippingMethodsProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethod;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethodType;

class MultiShippingCheckoutShippingMethodsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DefaultMultipleShippingMethodProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $multiShippingMethodProvider;

    /** @var CheckoutShippingContextProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutShippingContextProvider;

    /** @var MultiShippingCheckoutShippingMethodsProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->multiShippingMethodProvider = $this->createMock(DefaultMultipleShippingMethodProvider::class);
        $this->checkoutShippingContextProvider = $this->createMock(CheckoutShippingContextProvider::class);

        $this->provider = new MultiShippingCheckoutShippingMethodsProvider(
            $this->multiShippingMethodProvider,
            $this->checkoutShippingContextProvider
        );
    }

    public function testGetApplicableMethodsViews()
    {
        $methods = $this->provider->getApplicableMethodsViews(new Checkout());
        $this->assertTrue($methods->isEmpty());
    }

    public function testGetPrice()
    {
        $this->multiShippingMethodProvider->expects($this->once())
            ->method('hasShippingMethods')
            ->willReturn(true);

        $shippingMethodType = $this->createMock(MultiShippingMethodType::class);
        $shippingMethodType->expects($this->once())
            ->method('calculatePrice')
            ->willReturn(Price::create(15.00, 'USD'));

        $shippingMethod = $this->createMock(MultiShippingMethod::class);
        $shippingMethod->expects($this->once())
            ->method('getIdentifier')
            ->willReturn('multi_shipping');
        $shippingMethod->expects($this->once())
            ->method('getType')
            ->with('primary')
            ->willReturn($shippingMethodType);

        $this->multiShippingMethodProvider->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn($shippingMethod);

        $shippingContext = $this->createMock(ShippingContextInterface::class);
        $this->checkoutShippingContextProvider->expects($this->once())
            ->method('getContext')
            ->willReturn($shippingContext);

        $checkout = new Checkout();
        $checkout->setShippingMethod('multi_shipping');
        $checkout->setShippingMethodType('primary');
        $price = $this->provider->getPrice($checkout);

        $this->assertNotNull($price);
        $this->assertInstanceOf(Price::class, $price);
        $this->assertEquals(15.00, $price->getValue());
        $this->assertEquals('USD', $price->getCurrency());
    }

    public function testGetPriceIfShippingMethodIsNotMultiShippingType()
    {
        $this->multiShippingMethodProvider->expects($this->once())
            ->method('hasShippingMethods')
            ->willReturn(true);

        $shippingMethod = $this->createMock(MultiShippingMethod::class);
        $shippingMethod->expects($this->once())
            ->method('getIdentifier')
            ->willReturn('multi_shipping');
        $shippingMethod->expects($this->never())
            ->method('getType');

        $this->multiShippingMethodProvider->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn($shippingMethod);

        $this->checkoutShippingContextProvider->expects($this->never())
            ->method('getContext');

        $checkout = new Checkout();
        $checkout->setShippingMethod('flat_rate_1');
        $price = $this->provider->getPrice($checkout);

        $this->assertNull($price);
    }

    public function testGetPriceIfMultiShippingMethodINotConfigured()
    {
        $this->multiShippingMethodProvider->expects($this->once())
            ->method('hasShippingMethods')
            ->willReturn(false);

        $this->multiShippingMethodProvider->expects($this->never())
            ->method('getShippingMethod');

        $this->checkoutShippingContextProvider->expects($this->never())
            ->method('getContext');

        $checkout = new Checkout();
        $checkout->setShippingMethod('flat_rate_1');
        $price = $this->provider->getPrice($checkout);

        $this->assertNull($price);
    }
}
