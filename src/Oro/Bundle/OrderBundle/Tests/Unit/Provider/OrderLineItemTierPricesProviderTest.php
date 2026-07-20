<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Provider\OrderLineItemTierPricesProvider;
use Oro\Bundle\OrderBundle\Provider\OrderProductPriceProvider;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderLineItemTierPricesProviderTest extends TestCase
{
    private OrderProductPriceProvider&MockObject $orderProductPriceProvider;

    private ProductLineItemProductPriceProviderInterface&MockObject $productLineItemProductPriceProvider;

    private OrderLineItemTierPricesProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->orderProductPriceProvider = $this->createMock(OrderProductPriceProvider::class);
        $this->productLineItemProductPriceProvider = $this->createMock(
            ProductLineItemProductPriceProviderInterface::class
        );

        $this->provider = new OrderLineItemTierPricesProvider(
            $this->orderProductPriceProvider,
            $this->productLineItemProductPriceProvider
        );
    }

    public function testGetTierPricesForLineItemWhenNoOrder(): void
    {
        $lineItem = new OrderLineItem();

        $this->orderProductPriceProvider
            ->expects(self::never())
            ->method('getProductPricesForLineItems');

        $this->productLineItemProductPriceProvider
            ->expects(self::never())
            ->method('getProductLineItemProductPrices');

        $result = $this->provider->getTierPricesForLineItem($lineItem);

        self::assertSame([], $result);
    }

    public function testGetTierPricesForLineItemWhenNoProduct(): void
    {
        $order = new Order();
        $order->setCurrency('USD');

        $lineItem = new OrderLineItem();
        $lineItem->addOrder($order);

        $this->orderProductPriceProvider
            ->expects(self::never())
            ->method('getProductPricesForLineItems');

        $this->productLineItemProductPriceProvider
            ->expects(self::never())
            ->method('getProductLineItemProductPrices');

        $result = $this->provider->getTierPricesForLineItem($lineItem);

        self::assertSame([], $result);
    }

    public function testGetTierPricesForLineItemWhenNoCurrency(): void
    {
        $product = new Product();
        ReflectionUtil::setId($product, 1);

        $order = new Order();

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->addOrder($order);

        $this->orderProductPriceProvider
            ->expects(self::never())
            ->method('getProductPricesForLineItems');

        $result = $this->provider->getTierPricesForLineItem($lineItem);

        self::assertSame([], $result);
    }

    public function testGetTierPricesForSimpleProduct(): void
    {
        $product = new Product();
        ReflectionUtil::setId($product, 1);
        $product->setType(Product::TYPE_SIMPLE);

        $order = new Order();
        $order->setCurrency('USD');

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->addOrder($order);

        $productPrice1 = $this->createMock(ProductPriceDTO::class);
        $productPrice2 = $this->createMock(ProductPriceDTO::class);

        $this->orderProductPriceProvider
            ->expects(self::once())
            ->method('getProductPricesForLineItems')
            ->with(self::identicalTo($order), [$lineItem])
            ->willReturn([1 => [$productPrice1, $productPrice2]]);

        $expectedPrices = [
            $this->createMock(ProductPriceDTO::class),
            $this->createMock(ProductPriceDTO::class),
        ];

        $this->productLineItemProductPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemProductPrices')
            ->with(
                self::identicalTo($lineItem),
                self::callback(function ($arg) use ($productPrice1, $productPrice2) {
                    return $arg instanceof ProductPriceCollectionDTO
                        && count($arg) === 2
                        && $arg[0] === $productPrice1
                        && $arg[1] === $productPrice2;
                }),
                self::equalTo('USD')
            )
            ->willReturn($expectedPrices);

        $result = $this->provider->getTierPricesForLineItem($lineItem);

        self::assertSame([1 => $expectedPrices], $result);
    }

    public function testGetTierPricesForProductKitIncludesKitItemProductPrices(): void
    {
        $product = new Product();
        ReflectionUtil::setId($product, 1);
        $product->setType(Product::TYPE_KIT);

        $order = new Order();
        $order->setCurrency('EUR');

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->addOrder($order);

        // Prices for kit product and its kit item products returned by OrderProductPriceProvider.
        $priceKit = $this->createMock(ProductPriceDTO::class);
        $priceItem1 = $this->createMock(ProductPriceDTO::class);
        $priceItem2 = $this->createMock(ProductPriceDTO::class);

        $this->orderProductPriceProvider
            ->expects(self::once())
            ->method('getProductPricesForLineItems')
            ->with(self::identicalTo($order), [$lineItem])
            ->willReturn([1 => [$priceKit], 2 => [$priceItem1], 3 => [$priceItem2]]);

        $expectedPrices = [$this->createMock(ProductPriceDTO::class)];

        $this->productLineItemProductPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemProductPrices')
            ->with(
                self::identicalTo($lineItem),
                self::callback(static fn ($c) => $c instanceof ProductPriceCollectionDTO && count($c) === 3),
                self::equalTo('EUR')
            )
            ->willReturn($expectedPrices);

        $result = $this->provider->getTierPricesForLineItem($lineItem);

        self::assertArrayHasKey(1, $result);
        self::assertSame($expectedPrices, $result[1]);
        // Kit item product price arrays are preserved in result.
        self::assertArrayHasKey(2, $result);
        self::assertArrayHasKey(3, $result);
    }

    public function testGetTierPricesForLineItemWhenNoPricesReturned(): void
    {
        $product = new Product();
        ReflectionUtil::setId($product, 1);

        $order = new Order();
        $order->setCurrency('USD');

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->addOrder($order);

        $this->orderProductPriceProvider
            ->expects(self::once())
            ->method('getProductPricesForLineItems')
            ->willReturn([]);

        $this->productLineItemProductPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemProductPrices')
            ->with(
                self::identicalTo($lineItem),
                self::callback(static fn ($c) => $c instanceof ProductPriceCollectionDTO && count($c) === 0),
                'USD'
            )
            ->willReturn([]);

        $result = $this->provider->getTierPricesForLineItem($lineItem);

        self::assertSame([], $result);
    }

    public function testGetTierPricesForKitLineItemWhenNoPricesReturned(): void
    {
        $product = new Product();
        ReflectionUtil::setId($product, 1);
        $product->setType(Product::TYPE_KIT);

        $order = new Order();
        $order->setCurrency('EUR');

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->addOrder($order);

        // Prices for kit product and its kit item products returned by OrderProductPriceProvider.
        $priceKit = $this->createMock(ProductPriceDTO::class);
        $priceItem1 = $this->createMock(ProductPriceDTO::class);
        $priceItem2 = $this->createMock(ProductPriceDTO::class);

        $this->orderProductPriceProvider
            ->expects(self::once())
            ->method('getProductPricesForLineItems')
            ->with(self::identicalTo($order), [$lineItem])
            ->willReturn([1 => [$priceKit], 2 => [$priceItem1], 3 => [$priceItem2]]);

        $this->productLineItemProductPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemProductPrices')
            ->with(
                self::identicalTo($lineItem),
                self::callback(static fn ($c) => $c instanceof ProductPriceCollectionDTO && count($c) === 3),
                self::equalTo('EUR')
            )
            ->willReturn([]);

        $result = $this->provider->getTierPricesForLineItem($lineItem);

        self::assertSame([], $result);
    }

    // -------------------------------------------------------------------------
    // getTierPricesForLineItems tests
    // -------------------------------------------------------------------------

    public function testGetTierPricesForLineItemsWhenEmptyInput(): void
    {
        $this->orderProductPriceProvider
            ->expects(self::never())
            ->method('getProductPricesForLineItems');

        $result = $this->provider->getTierPricesForLineItems([]);

        self::assertSame([], $result);
    }

    public function testGetTierPricesForLineItemsWhenNoOrderFound(): void
    {
        $product = new Product();
        ReflectionUtil::setId($product, 1);

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        // no order attached

        $this->orderProductPriceProvider
            ->expects(self::never())
            ->method('getProductPricesForLineItems');

        $result = $this->provider->getTierPricesForLineItems(['a' => $lineItem]);

        self::assertSame(['a' => []], $result);
    }

    public function testGetTierPricesForLineItemsWhenNoCurrency(): void
    {
        $product = new Product();
        ReflectionUtil::setId($product, 1);

        $order = new Order();
        // no currency set

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->addOrder($order);

        $this->orderProductPriceProvider
            ->expects(self::never())
            ->method('getProductPricesForLineItems');

        $result = $this->provider->getTierPricesForLineItems([0 => $lineItem]);

        self::assertSame([0 => []], $result);
    }

    public function testGetTierPricesForLineItemsWhenNoProducts(): void
    {
        $order = new Order();
        $order->setCurrency('USD');

        $lineItem = new OrderLineItem();
        $lineItem->addOrder($order);
        // no product on line item

        $this->orderProductPriceProvider
            ->expects(self::once())
            ->method('getProductPricesForLineItems')
            ->with(self::identicalTo($order), [0 => $lineItem])
            ->willReturn([]);

        $this->productLineItemProductPriceProvider
            ->expects(self::never())
            ->method('getProductLineItemProductPrices');

        $result = $this->provider->getTierPricesForLineItems([0 => $lineItem]);

        self::assertSame([0 => []], $result);
    }

    public function testGetTierPricesForLineItemsSimpleProducts(): void
    {
        $product1 = new Product();
        ReflectionUtil::setId($product1, 10);
        $product1->setType(Product::TYPE_SIMPLE);

        $product2 = new Product();
        ReflectionUtil::setId($product2, 20);
        $product2->setType(Product::TYPE_SIMPLE);

        $order = new Order();
        $order->setCurrency('USD');

        $lineItem1 = new OrderLineItem();
        $lineItem1->setProduct($product1);
        $lineItem1->addOrder($order);

        $lineItem2 = new OrderLineItem();
        $lineItem2->setProduct($product2);
        $lineItem2->addOrder($order);

        $price10a = $this->createMock(ProductPriceDTO::class);
        $price10b = $this->createMock(ProductPriceDTO::class);
        $price20a = $this->createMock(ProductPriceDTO::class);

        $this->orderProductPriceProvider
            ->expects(self::once())
            ->method('getProductPricesForLineItems')
            ->with(
                self::identicalTo($order),
                [0 => $lineItem1, 1 => $lineItem2]
            )
            ->willReturn([10 => [$price10a, $price10b], 20 => [$price20a]]);

        $this->productLineItemProductPriceProvider
            ->expects(self::exactly(2))
            ->method('getProductLineItemProductPrices')
            ->willReturnCallback(
                function (OrderLineItem $lineItem) use (
                    $lineItem1,
                    $lineItem2,
                    $price10a,
                    $price10b,
                    $price20a
                ): array {
                    return match (true) {
                        $lineItem === $lineItem1 => [$price10a, $price10b],
                        $lineItem === $lineItem2 => [$price20a],
                        default => [],
                    };
                }
            );

        $result = $this->provider->getTierPricesForLineItems([0 => $lineItem1, 1 => $lineItem2]);

        self::assertSame([$price10a, $price10b], $result[0]);
        self::assertSame([$price20a], $result[1]);
    }

    public function testGetTierPricesForLineItemsWithSameProductInMultipleLineItems(): void
    {
        $product = new Product();
        ReflectionUtil::setId($product, 5);
        $product->setType(Product::TYPE_SIMPLE);

        $order = new Order();
        $order->setCurrency('EUR');

        $lineItem1 = new OrderLineItem();
        $lineItem1->setProduct($product);
        $lineItem1->addOrder($order);

        $lineItem2 = new OrderLineItem();
        $lineItem2->setProduct($product);
        $lineItem2->addOrder($order);

        $price = $this->createMock(ProductPriceDTO::class);

        $this->orderProductPriceProvider
            ->expects(self::once())
            ->method('getProductPricesForLineItems')
            ->willReturn([5 => [$price]]);

        $this->productLineItemProductPriceProvider
            ->expects(self::exactly(2))
            ->method('getProductLineItemProductPrices')
            ->willReturn([$price]);

        $result = $this->provider->getTierPricesForLineItems(['x' => $lineItem1, 'y' => $lineItem2]);

        self::assertSame([$price], $result['x']);
        self::assertSame([$price], $result['y']);
    }

    public function testGetTierPricesForLineItemsKitProduct(): void
    {
        $kitProduct = new Product();
        ReflectionUtil::setId($kitProduct, 1);
        $kitProduct->setType(Product::TYPE_KIT);

        $order = new Order();
        $order->setCurrency('USD');

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($kitProduct);
        $lineItem->addOrder($order);

        $priceKit = $this->createMock(ProductPriceDTO::class);
        $priceItem = $this->createMock(ProductPriceDTO::class);

        $this->orderProductPriceProvider
            ->expects(self::once())
            ->method('getProductPricesForLineItems')
            ->willReturn([1 => [$priceKit], 2 => [$priceItem]]);

        $matchedPrices = [$this->createMock(ProductPriceDTO::class)];

        $this->productLineItemProductPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemProductPrices')
            ->with(
                self::identicalTo($lineItem),
                self::callback(static fn ($c) => $c instanceof ProductPriceCollectionDTO && count($c) === 2),
                self::equalTo('USD')
            )
            ->willReturn($matchedPrices);

        $result = $this->provider->getTierPricesForLineItems([0 => $lineItem]);

        self::assertArrayHasKey(0, $result);
        self::assertSame($matchedPrices, $result[0]);
    }

    public function testGetTierPricesForLineItemsMixedLineItems(): void
    {
        $simpleProduct = new Product();
        ReflectionUtil::setId($simpleProduct, 10);
        $simpleProduct->setType(Product::TYPE_SIMPLE);

        $kitProduct = new Product();
        ReflectionUtil::setId($kitProduct, 20);
        $kitProduct->setType(Product::TYPE_KIT);

        $order = new Order();
        $order->setCurrency('GBP');

        $simpleLineItem = new OrderLineItem();
        $simpleLineItem->setProduct($simpleProduct);
        $simpleLineItem->addOrder($order);

        $kitLineItem = new OrderLineItem();
        $kitLineItem->setProduct($kitProduct);
        $kitLineItem->addOrder($order);

        $simplePrice = $this->createMock(ProductPriceDTO::class);
        $kitPrice = $this->createMock(ProductPriceDTO::class);

        $this->orderProductPriceProvider
            ->expects(self::once())
            ->method('getProductPricesForLineItems')
            ->willReturn([10 => [$simplePrice], 20 => [$kitPrice]]);

        $matchedKitPrices = [$this->createMock(ProductPriceDTO::class)];

        $this->productLineItemProductPriceProvider
            ->expects(self::exactly(2))
            ->method('getProductLineItemProductPrices')
            ->willReturnCallback(
                function (OrderLineItem $lineItem) use (
                    $simpleLineItem,
                    $kitLineItem,
                    $simplePrice,
                    $matchedKitPrices
                ): array {
                    return match (true) {
                        $lineItem === $simpleLineItem => [$simplePrice],
                        $lineItem === $kitLineItem => $matchedKitPrices,
                        default => [],
                    };
                }
            );

        $result = $this->provider->getTierPricesForLineItems(['s' => $simpleLineItem, 'k' => $kitLineItem]);

        self::assertSame([$simplePrice], $result['s']);
        self::assertSame($matchedKitPrices, $result['k']);
    }
    // -------------------------------------------------------------------------
}
