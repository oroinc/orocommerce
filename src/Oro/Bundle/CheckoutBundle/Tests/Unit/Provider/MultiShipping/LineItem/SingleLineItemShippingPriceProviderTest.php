<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider\MultiShipping\LineItem;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItem\SingleLineItemShippingPriceProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Provider\Price\ShippingPriceProviderInterface;

class SingleLineItemShippingPriceProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingPriceProvider;

    /** @var CheckoutShippingContextProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutShippingContextProvider;

    /** @var CheckoutFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutFactory;

    /** @var SingleLineItemShippingPriceProvider */
    private $priceProvider;

    protected function setUp(): void
    {
        $this->shippingPriceProvider = $this->createMock(ShippingPriceProviderInterface::class);
        $this->checkoutShippingContextProvider = $this->createMock(CheckoutShippingContextProvider::class);
        $this->checkoutFactory = $this->createMock(CheckoutFactoryInterface::class);

        $this->priceProvider = new SingleLineItemShippingPriceProvider(
            $this->shippingPriceProvider,
            $this->checkoutShippingContextProvider,
            $this->checkoutFactory
        );
    }

    public function testGetPrice()
    {
        $checkout = new Checkout();
        $lineItem = new CheckoutLineItem();
        $checkout->addLineItem($lineItem);
        $lineItem->setCheckout($checkout);
        $lineItem->setShippingMethod('method1');
        $lineItem->setShippingMethodType('type1');

        $this->checkoutFactory->expects($this->once())
            ->method('createCheckout')
            ->willReturn($checkout);

        $shippingContext = $this->createMock(ShippingContextInterface::class);
        $this->checkoutShippingContextProvider->expects($this->once())
            ->method('getContext')
            ->willReturn($shippingContext);

        $this->shippingPriceProvider->expects($this->once())
            ->method('getPrice')
            ->with(
                self::identicalTo($shippingContext),
                $lineItem->getShippingMethod(),
                $lineItem->getShippingMethodType()
            )
            ->willReturn(Price::create(10.00, 'USD'));

        $price = $this->priceProvider->getPrice($lineItem);

        $this->assertInstanceOf(Price::class, $price);
        $this->assertEquals(10.00, $price->getValue());
        $this->assertEquals('USD', $price->getCurrency());
    }

    public function testGetPriceWhenPriceIsNull()
    {
        $checkout = new Checkout();
        $lineItem = new CheckoutLineItem();
        $checkout->addLineItem($lineItem);
        $lineItem->setCheckout($checkout);
        $lineItem->setCurrency('USD');

        $this->checkoutFactory->expects($this->once())
            ->method('createCheckout')
            ->willReturn($checkout);

        $shippingContext = $this->createMock(ShippingContextInterface::class);
        $this->checkoutShippingContextProvider->expects($this->once())
            ->method('getContext')
            ->willReturn($shippingContext);

        $this->shippingPriceProvider->expects($this->once())
            ->method('getPrice')
            ->with(self::identicalTo($shippingContext), self::isNull(), self::isNull())
            ->willReturn(null);

        $price = $this->priceProvider->getPrice($lineItem);

        $this->assertInstanceOf(Price::class, $price);
        $this->assertEquals(0.00, $price->getValue());
        $this->assertEquals('USD', $price->getCurrency());
    }
}
