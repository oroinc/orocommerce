<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\Order\OrderLineItemTierPricesEventListener;
use Oro\Bundle\OrderBundle\Provider\OrderProductPriceProvider;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;

class OrderLineItemTierPricesEventListenerTest extends TestCase
{
    private OrderProductPriceProvider|MockObject $orderProductPriceProvider;

    private ProductLineItemProductPriceProviderInterface|MockObject $productLineItemProductPriceProvider;

    private OrderLineItemTierPricesEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->orderProductPriceProvider = $this->createMock(OrderProductPriceProvider::class);
        $this->productLineItemProductPriceProvider = $this->createMock(
            ProductLineItemProductPriceProviderInterface::class
        );

        $this->listener = new OrderLineItemTierPricesEventListener(
            $this->orderProductPriceProvider,
            $this->productLineItemProductPriceProvider
        );
    }

    public function testOrderEventWhenNoLineItems(): void
    {
        $order = new Order();
        $event = new OrderEvent($this->createMock(FormInterface::class), $order, []);

        $this->orderProductPriceProvider
            ->expects(self::once())
            ->method('getProductPrices')
            ->with($order)
            ->willReturn([]);

        $this->productLineItemProductPriceProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onOrderEvent($event);

        self::assertSame(
            [OrderLineItemTierPricesEventListener::TIER_PRICES_KEY => []],
            $event->getData()->getArrayCopy()
        );
    }

    public function testOrderEventWhenNoLineItemsWithProduct(): void
    {
        $lineItem = new OrderLineItem();
        $order = (new Order())
            ->addLineItem($lineItem);
        $event = new OrderEvent($this->createMock(FormInterface::class), $order, []);

        $this->orderProductPriceProvider
            ->expects(self::once())
            ->method('getProductPrices')
            ->with($order)
            ->willReturn([]);

        $this->productLineItemProductPriceProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onOrderEvent($event);

        self::assertSame(
            [OrderLineItemTierPricesEventListener::TIER_PRICES_KEY => []],
            $event->getData()->getArrayCopy()
        );
    }

    public function testOrderEventWhenHasLineItemWithProductButNoPrices(): void
    {
        $product = (new ProductStub())->setId(42);
        $lineItem = (new OrderLineItem())
            ->setProduct($product);
        $order = (new Order())
            ->addLineItem($lineItem)
            ->setCurrency('USD');
        $event = new OrderEvent($this->createMock(FormInterface::class), $order, []);

        $this->orderProductPriceProvider
            ->expects(self::once())
            ->method('getProductPrices')
            ->with($order)
            ->willReturn([]);

        $this->productLineItemProductPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemProductPrices')
            ->with($lineItem, new ProductPriceCollectionDTO(), $order->getCurrency())
            ->willReturn([]);

        $this->listener->onOrderEvent($event);

        self::assertSame(
            [OrderLineItemTierPricesEventListener::TIER_PRICES_KEY => []],
            $event->getData()->getArrayCopy()
        );
    }

    public function testOrderEventWhenHasLineItemWithProductAndHasPrices(): void
    {
        $product = (new ProductStub())->setId(42);
        $lineItem = (new OrderLineItem())
            ->setProduct($product);
        $order = (new Order())
            ->addLineItem($lineItem)
            ->setCurrency('USD');
        $event = new OrderEvent($this->createMock(FormInterface::class), $order, []);

        $productPrice1 = $this->createMock(ProductPriceInterface::class);
        $productPrice2 = $this->createMock(ProductPriceInterface::class);
        $this->orderProductPriceProvider
            ->expects(self::once())
            ->method('getProductPrices')
            ->with($order)
            ->willReturn([$product->getId() => [$productPrice1, $productPrice2]]);

        $this->productLineItemProductPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemProductPrices')
            ->with($lineItem, new ProductPriceCollectionDTO([$productPrice1, $productPrice2]), $order->getCurrency())
            ->willReturn([$productPrice1, $productPrice2]);

        $this->listener->onOrderEvent($event);

        self::assertSame(
            [
                OrderLineItemTierPricesEventListener::TIER_PRICES_KEY => [
                    $product->getId() => [
                        $productPrice1,
                        $productPrice2,
                    ],
                ],
            ],
            $event->getData()->getArrayCopy()
        );
    }

    public function testOrderEventWhenHasLineItemWithProductKit(): void
    {
        $product = (new ProductStub())
            ->setId(42)
            ->setType(Product::TYPE_KIT);
        $lineItem1 = (new OrderLineItem())
            ->setProduct($product)
            ->setChecksum('checksum1');
        $lineItem2 = (new OrderLineItem())
            ->setProduct($product)
            ->setChecksum('checksum2');
        $order = (new Order())
            ->addLineItem($lineItem1)
            ->addLineItem($lineItem2)
            ->setCurrency('USD');
        $event = new OrderEvent($this->createMock(FormInterface::class), $order, []);

        $productPrice1 = $this->createMock(ProductPriceInterface::class);
        $productPrice2 = $this->createMock(ProductPriceInterface::class);
        $productPrice3 = $this->createMock(ProductPriceInterface::class);
        $this->orderProductPriceProvider
            ->expects(self::once())
            ->method('getProductPrices')
            ->with($order)
            ->willReturn([$product->getId() => [$productPrice1, $productPrice2, $productPrice3]]);

        $productPriceCollection = new ProductPriceCollectionDTO([$productPrice1, $productPrice2, $productPrice3]);
        $this->productLineItemProductPriceProvider
            ->expects(self::exactly(2))
            ->method('getProductLineItemProductPrices')
            ->withConsecutive(
                [$lineItem1, $productPriceCollection, $order->getCurrency()],
                [$lineItem2, $productPriceCollection, $order->getCurrency()],
            )
            ->willReturnOnConsecutiveCalls(
                [$productPrice1, $productPrice2],
                [$productPrice3],
            );

        $this->listener->onOrderEvent($event);

        self::assertSame(
            [
                OrderLineItemTierPricesEventListener::TIER_PRICES_KEY => [
                    $product->getId() => [
                        $lineItem1->getChecksum() => [$productPrice1, $productPrice2],
                        $lineItem2->getChecksum() => [$productPrice3],
                    ],
                ],
            ],
            $event->getData()->getArrayCopy()
        );
    }
}
