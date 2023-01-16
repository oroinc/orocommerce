<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Shipping\Chain\Member\Price;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\DefaultMultipleShippingMethodProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member\Price\MultiShippingPriceProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethod;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethodType;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;

class MultiShippingPriceProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DefaultMultipleShippingMethodProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingMethodProvider;

    /** @var CheckoutShippingContextProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutShippingContextProvider;

    /** @var MultiShippingPriceProvider */
    private $multiShippingPriceProvider;

    protected function setUp(): void
    {
        $this->shippingMethodProvider = $this->createMock(DefaultMultipleShippingMethodProvider::class);
        $this->checkoutShippingContextProvider = $this->createMock(CheckoutShippingContextProvider::class);

        $this->multiShippingPriceProvider = new MultiShippingPriceProvider(
            $this->shippingMethodProvider,
            $this->checkoutShippingContextProvider
        );
    }

    public function testGetApplicableMethodsViews()
    {
        $methods = $this->multiShippingPriceProvider->getApplicableMethodsViews(new Checkout());
        $this->assertTrue($methods->isEmpty());
    }

    public function testGetApplicableMethodsViewsWithSuccessor()
    {
        $successor = $this->getSuccessor();
        $this->multiShippingPriceProvider->setSuccessor($successor);

        $methods = $this->multiShippingPriceProvider->getApplicableMethodsViews(new Checkout());
        $this->assertFalse($methods->isEmpty());
        $this->assertCount(1, $methods->getAllMethodsViews());
        $this->assertCount(1, $methods->getAllMethodsTypesViews());
    }

    public function testGetPrice()
    {
        $this->shippingMethodProvider->expects($this->once())
            ->method('hasShippingMethods')
            ->willReturn(true);

        $shippingMethodMock = $this->createMock(MultiShippingMethod::class);
        $shippingMethodMock->expects($this->once())
            ->method('getIdentifier')
            ->willReturn('multi_shipping');

        $shippingMethodTypeMock = $this->createMock(MultiShippingMethodType::class);
        $shippingMethodMock->expects($this->once())
            ->method('getType')
            ->willReturn($shippingMethodTypeMock);

        $shippingMethodTypeMock->expects($this->once())
            ->method('calculatePrice')
            ->willReturn(Price::create(15.00, 'USD'));

        $this->shippingMethodProvider->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn($shippingMethodMock);

        $shippingContextMock = $this->createMock(ShippingContextInterface::class);
        $this->checkoutShippingContextProvider->expects($this->once())
            ->method('getContext')
            ->willReturn($shippingContextMock);

        $checkout = new Checkout();
        $checkout->setShippingMethod('multi_shipping');
        $price = $this->multiShippingPriceProvider->getPrice($checkout);

        $this->assertNotNull($price);
        $this->assertInstanceOf(Price::class, $price);
        $this->assertEquals(15.00, $price->getValue());
        $this->assertEquals('USD', $price->getCurrency());
    }

    public function testGetPriceIfShippingMethodIsNotMultiShippingType()
    {
        $this->shippingMethodProvider->expects($this->once())
            ->method('hasShippingMethods')
            ->willReturn(true);

        $shippingMethodMock = $this->createMock(MultiShippingMethod::class);
        $shippingMethodMock->expects($this->once())
            ->method('getIdentifier')
            ->willReturn('multi_shipping');

        $shippingMethodMock->expects($this->never())
            ->method('getType');

        $this->shippingMethodProvider->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn($shippingMethodMock);

        $this->checkoutShippingContextProvider->expects($this->never())
            ->method('getContext');

        $checkout = new Checkout();
        $checkout->setShippingMethod('flat_rate_1');
        $price = $this->multiShippingPriceProvider->getPrice($checkout);

        $this->assertNull($price);
    }

    public function testGetPriceIfMultiShippingMethodINotConfigured()
    {
        $this->shippingMethodProvider->expects($this->once())
            ->method('hasShippingMethods')
            ->willReturn(false);

        $this->shippingMethodProvider->expects($this->never())
            ->method('getShippingMethod');

        $this->checkoutShippingContextProvider->expects($this->never())
            ->method('getContext');

        $checkout = new Checkout();
        $checkout->setShippingMethod('flat_rate_1');
        $price = $this->multiShippingPriceProvider->getPrice($checkout);

        $this->assertNull($price);
    }

    public function testGetPriceWithSuccessor()
    {
        $this->shippingMethodProvider->expects($this->never())
            ->method('hasShippingMethods');

        $this->shippingMethodProvider->expects($this->never())
            ->method('getShippingMethod');

        $this->checkoutShippingContextProvider->expects($this->never())
            ->method('getContext');

        $successor = $this->getSuccessor(Price::create(10.00, 'USD'));
        $this->multiShippingPriceProvider->setSuccessor($successor);

        $checkout = new Checkout();
        $checkout->setShippingMethod('flat_rate_1');
        $price = $this->multiShippingPriceProvider->getPrice($checkout);

        $this->assertNotNull($price);
        $this->assertInstanceOf(Price::class, $price);
        $this->assertEquals(10.00, $price->getValue());
        $this->assertEquals('USD', $price->getCurrency());
    }

    public function testGetPriceWhenSuccessorReturnsNull()
    {
        $this->shippingMethodProvider->expects($this->once())
            ->method('hasShippingMethods')
            ->willReturn(true);

        $shippingMethodMock = $this->createMock(MultiShippingMethod::class);
        $shippingMethodMock->expects($this->once())
            ->method('getIdentifier')
            ->willReturn('multi_shipping');

        $shippingMethodTypeMock = $this->createMock(MultiShippingMethodType::class);
        $shippingMethodMock->expects($this->once())
            ->method('getType')
            ->willReturn($shippingMethodTypeMock);

        $shippingMethodTypeMock->expects($this->once())
            ->method('calculatePrice')
            ->willReturn(Price::create(15.00, 'USD'));

        $this->shippingMethodProvider->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn($shippingMethodMock);

        $shippingContextMock = $this->createMock(ShippingContextInterface::class);
        $this->checkoutShippingContextProvider->expects($this->once())
            ->method('getContext')
            ->willReturn($shippingContextMock);

        $successor = $this->getSuccessor();
        $this->multiShippingPriceProvider->setSuccessor($successor);

        $checkout = new Checkout();
        $checkout->setShippingMethod('multi_shipping');
        $price = $this->multiShippingPriceProvider->getPrice($checkout);

        $this->assertNotNull($price);
        $this->assertInstanceOf(Price::class, $price);
        $this->assertEquals(15.00, $price->getValue());
        $this->assertEquals('USD', $price->getCurrency());
    }

    private function getSuccessor(?Price $shippingPrice = null): CheckoutShippingMethodsProviderInterface
    {
        $successor = $this->createMock(CheckoutShippingMethodsProviderInterface::class);
        $successor->expects(self::any())
            ->method('getApplicableMethodsViews')
            ->willReturnCallback(function () {
                $shippingMethodViewCollection = new ShippingMethodViewCollection();
                $shippingMethodViewCollection->addMethodView(
                    'test_shipping_1',
                    ['identifier' => 'test_shipping_1', 'label' => 'Test Shipping']
                );
                $shippingMethodViewCollection->addMethodTypeView(
                    'test_shipping_1',
                    'test_shipping_type_1',
                    ['identifier' => 'test_shipping_type_1', 'label' => 'Test Type']
                );

                return $shippingMethodViewCollection;
            });
        $successor->expects(self::any())
            ->method('getPrice')
            ->willReturn($shippingPrice);

        return $successor;
    }
}
