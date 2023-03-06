<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\DataProvider\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutToOrderConverter;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\CheckoutBundle\Payment\Method\EntityPaymentMethodsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\SplitCheckoutProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Component\Testing\ReflectionUtil;

class CheckoutToOrderConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutLineItemsManager|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutLineItemsManager;

    /** @var MapperInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $mapper;

    /** @var EntityPaymentMethodsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMethodsProvider;

    /** @var SplitCheckoutProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $splitCheckoutProvider;

    /** @var MemoryCacheProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $memoryCacheProvider;

    /** @var CheckoutToOrderConverter */
    private $converter;

    protected function setUp(): void
    {
        $this->checkoutLineItemsManager = $this->createMock(CheckoutLineItemsManager::class);
        $this->mapper = $this->createMock(MapperInterface::class);
        $this->paymentMethodsProvider = $this->createMock(EntityPaymentMethodsProvider::class);
        $this->splitCheckoutProvider = $this->createMock(SplitCheckoutProvider::class);
        $this->memoryCacheProvider = $this->createMock(MemoryCacheProviderInterface::class);

        $this->converter = new CheckoutToOrderConverter(
            $this->checkoutLineItemsManager,
            $this->mapper,
            $this->paymentMethodsProvider,
            $this->splitCheckoutProvider,
            $this->memoryCacheProvider
        );
    }

    private function getCheckout(string $paymentMethod, ?int $id = null): Checkout
    {
        $checkout = new Checkout();
        $checkout->setPaymentMethod($paymentMethod);
        if (null !== $id) {
            ReflectionUtil::setId($checkout, 1);
        }

        return $checkout;
    }

    public function testGetOrderWithMemoryCache(): void
    {
        $checkout = $this->getCheckout('pm1');
        $order = new Order();

        $lineItems = new ArrayCollection([new OrderLineItem()]);

        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->with($checkout)
            ->willReturn($lineItems);

        $this->mapper->expects($this->once())
            ->method('map')
            ->with($checkout, ['lineItems' => $lineItems])
            ->willReturn($order);

        $this->paymentMethodsProvider->expects($this->once())
            ->method('storePaymentMethodsToEntity')
            ->with($order, ['pm1']);

        $this->splitCheckoutProvider->expects($this->never())
            ->method('getSubCheckouts');

        $this->memoryCacheProvider->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($cacheKeyArguments, $callable) {
                return $callable($cacheKeyArguments);
            });

        $this->assertSame($order, $this->converter->getOrder($checkout));
    }

    public function testGetOrderWithMemoryCacheAndCachedData(): void
    {
        $checkout = $this->getCheckout('pm1');
        $order = new Order();

        $this->checkoutLineItemsManager->expects($this->never())
            ->method($this->anything());

        $this->mapper->expects($this->never())
            ->method($this->anything());

        $this->paymentMethodsProvider->expects($this->once())
            ->method('storePaymentMethodsToEntity')
            ->with($order, ['pm1']);

        $this->splitCheckoutProvider->expects($this->never())
            ->method('getSubCheckouts');

        $this->memoryCacheProvider->expects($this->once())
            ->method('get')
            ->willReturnCallback(function () use ($order) {
                return $order;
            });

        $this->assertSame($order, $this->converter->getOrder($checkout));
    }

    public function testGetOrderWithCreateSubOrdersEnabled(): void
    {
        $checkout = $this->getCheckout('pm1', 1);
        $subCheckout1 = $this->getCheckout('pm1');
        $subCheckout2 = $this->getCheckout('pm1');

        $order = new Order();
        $subOrder1 = new Order();
        $subOrder2 = new Order();

        $lineItems = new ArrayCollection([new OrderLineItem()]);
        $subOrderLineItems1 = new ArrayCollection([new OrderLineItem()]);
        $subOrderLineItems2 = new ArrayCollection([new OrderLineItem()]);

        $this->checkoutLineItemsManager->expects($this->exactly(3))
            ->method('getData')
            ->willReturnMap([
                [$checkout, false, 'oro_order.frontend_product_visibility', $lineItems],
                [$subCheckout1, false, 'oro_order.frontend_product_visibility', $subOrderLineItems1],
                [$subCheckout2, false, 'oro_order.frontend_product_visibility', $subOrderLineItems2]
            ]);

        $this->mapper->expects($this->exactly(3))
            ->method('map')
            ->willReturnMap([
                [$checkout, ['lineItems' => $lineItems], [], $order],
                [$subCheckout1, ['lineItems' => $subOrderLineItems1], [], $subOrder1],
                [$subCheckout2, ['lineItems' => $subOrderLineItems2], [], $subOrder2]
            ]);

        $this->paymentMethodsProvider->expects($this->exactly(3))
            ->method('storePaymentMethodsToEntity')
            ->withConsecutive(
                [$order, ['pm1']],
                [$subOrder1, ['pm1']],
                [$subOrder2, ['pm1']]
            );

        $this->splitCheckoutProvider->expects($this->once())
            ->method('getSubCheckouts')
            ->willReturn([$subCheckout1, $subCheckout2]);

        $this->memoryCacheProvider->expects($this->exactly(3))
            ->method('get')
            ->willReturnCallback(function ($cacheKeyArguments, $callable) {
                return $callable($cacheKeyArguments);
            });

        $this->assertSame($order, $this->converter->getOrder($checkout));
        $this->assertCount(2, $order->getSubOrders());
    }

    public function testGetOrderWithEmptySubCheckouts(): void
    {
        $checkout = $this->getCheckout('pm1', 1);

        $order = new Order();

        $lineItems = new ArrayCollection([new OrderLineItem()]);

        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->with($checkout)
            ->willReturn($lineItems);

        $this->mapper->expects($this->once())
            ->method('map')
            ->with($checkout, ['lineItems' => $lineItems])
            ->willReturn($order);

        $this->paymentMethodsProvider->expects($this->once())
            ->method('storePaymentMethodsToEntity')
            ->with($order, ['pm1']);

        $this->splitCheckoutProvider->expects($this->once())
            ->method('getSubCheckouts')
            ->willReturn([]);

        $this->memoryCacheProvider->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($cacheKeyArguments, $callable) {
                return $callable($cacheKeyArguments);
            });

        $this->assertSame($order, $this->converter->getOrder($checkout));
        $this->assertCount(0, $order->getSubOrders());
    }
}
