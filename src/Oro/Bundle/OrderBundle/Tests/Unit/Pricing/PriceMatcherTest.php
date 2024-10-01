<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Pricing;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\OrderBundle\Pricing\PriceMatcher;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitItemLineItemPrice;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitLineItemPrice;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PriceMatcherTest extends TestCase
{
    private ProductLineItemPriceProviderInterface|MockObject $productLineItemPriceProvider;

    private ProductPriceScopeCriteriaFactoryInterface|MockObject $priceScopeCriteriaFactory;

    private Order|MockObject $order;

    private OrderLineItem|MockObject $orderLineItem;

    private ProductPriceCriteria|MockObject $productPriceCriteria;

    private PriceMatcher $matcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->productLineItemPriceProvider = $this->createMock(ProductLineItemPriceProviderInterface::class);
        $this->priceScopeCriteriaFactory = $this->createMock(ProductPriceScopeCriteriaFactoryInterface::class);

        $this->order = $this->createMock(Order::class);
        $this->orderLineItem = $this->createMock(OrderLineItem::class);
        $this->productPriceCriteria = $this->createMock(ProductPriceCriteria::class);

        $this->matcher = new PriceMatcher(
            $this->productLineItemPriceProvider,
            $this->priceScopeCriteriaFactory
        );
    }

    public function testAddMatchingPricesWhenNoLineItems(): void
    {
        $this->productLineItemPriceProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->matcher->addMatchingPrices(new Order());
    }

    public function testAddMatchingPricesWhenLineItemsWithoutProduct(): void
    {
        $this->productLineItemPriceProvider
            ->expects(self::never())
            ->method(self::anything());

        $order = new Order();
        $lineItem = (new OrderLineItem())
            ->setPrice(Price::create(12.3456, 'USD'));
        $order->addLineItem($lineItem);

        $this->matcher->addMatchingPrices($order);
    }

    public function testAddMatchingPricesWhenLineItemsWithPrice(): void
    {
        $this->productLineItemPriceProvider
            ->expects(self::never())
            ->method(self::anything());

        $order = new Order();
        $lineItem = (new OrderLineItem())
            ->setProduct(new Product())
            ->setPrice(Price::create(12.3456, 'USD'));
        $order->addLineItem($lineItem);

        $this->matcher->addMatchingPrices($order);
    }

    public function testAddMatchingPricesWhenNoPricesFound(): void
    {
        $order = (new Order())
            ->setCurrency('USD');
        $lineItem = (new OrderLineItem())
            ->setProduct(new Product());
        $order->addLineItem($lineItem);
        $lineItemWithPriceValue = (new OrderLineItem())
            ->setProduct(new Product())
            ->setValue(12.3456);
        $order->addLineItem($lineItemWithPriceValue);
        $lineItemWithPriceCurrency = (new OrderLineItem())
            ->setProduct(new Product())
            ->setCurrency('USD');
        $order->addLineItem($lineItemWithPriceCurrency);

        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with($order)
            ->willReturn($priceScopeCriteria);

        $this->productLineItemPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with(
                [$lineItem, $lineItemWithPriceValue, $lineItemWithPriceCurrency],
                $priceScopeCriteria,
                $order->getCurrency()
            )
            ->willReturn([]);

        $this->matcher->addMatchingPrices($order);

        self::assertEquals(null, $lineItem->getValue());
        self::assertEquals(null, $lineItem->getCurrency());
        self::assertEquals(12.3456, $lineItemWithPriceValue->getValue());
        self::assertEquals(null, $lineItemWithPriceValue->getCurrency());
        self::assertEquals(null, $lineItemWithPriceCurrency->getValue());
        self::assertEquals('USD', $lineItemWithPriceCurrency->getCurrency());
    }

    public function testAddMatchingPricesWhenMatchingPriceFound(): void
    {
        $order = (new Order())
            ->setCurrency('USD');
        $lineItem = (new OrderLineItem())
            ->setProduct(new Product());
        $order->addLineItem($lineItem);

        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with($order)
            ->willReturn($priceScopeCriteria);

        $productLineItemPrice = new ProductLineItemPrice($lineItem, Price::create(34.5678, 'USD'), 34.5678);
        $this->productLineItemPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([$lineItem], $priceScopeCriteria, $order->getCurrency())
            ->willReturn([$productLineItemPrice]);

        $this->matcher->addMatchingPrices($order);

        self::assertEquals($productLineItemPrice->getPrice(), $lineItem->getPrice());
        self::assertNotSame($productLineItemPrice->getPrice(), $lineItem->getPrice());
        self::assertEquals($productLineItemPrice->getPrice()->getValue(), $lineItem->getValue());
        self::assertEquals($productLineItemPrice->getPrice()->getCurrency(), $lineItem->getCurrency());
    }

    public function testAddMatchingPricesWhenProductKitLineItemAndNoMatchingPriceFound(): void
    {
        $order = (new Order())
            ->setCurrency('USD');
        $productKit = (new Product())
            ->setType(Product::TYPE_KIT);

        $kitItemLineItem = new OrderProductKitItemLineItem();
        $lineItem = (new OrderLineItem())
            ->setProduct($productKit)
            ->addKitItemLineItem($kitItemLineItem);
        $order->addLineItem($lineItem);

        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with($order)
            ->willReturn($priceScopeCriteria);

        $this->productLineItemPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([$lineItem], $priceScopeCriteria, $order->getCurrency())
            ->willReturn([]);

        $this->matcher->addMatchingPrices($order);

        self::assertNull($lineItem->getPrice());
        self::assertNull($lineItem->getValue());
        self::assertNull($lineItem->getCurrency());
    }

    public function testAddMatchingPricesWhenProductKitLineItemAndNotProductKitLineItemPrice(): void
    {
        $order = (new Order())
            ->setCurrency('USD');
        $productKit = (new Product())
            ->setType(Product::TYPE_KIT);

        $kitItemLineItem = new OrderProductKitItemLineItem();
        $lineItem = (new OrderLineItem())
            ->setProduct($productKit)
            ->addKitItemLineItem($kitItemLineItem);
        $order->addLineItem($lineItem);

        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with($order)
            ->willReturn($priceScopeCriteria);

        $productLineItemPrice = new ProductLineItemPrice($lineItem, Price::create(34.5678, 'USD'), 34.5678);
        $this->productLineItemPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([$lineItem], $priceScopeCriteria, $order->getCurrency())
            ->willReturn([$productLineItemPrice]);

        $this->matcher->addMatchingPrices($order);

        self::assertEquals($productLineItemPrice->getPrice(), $lineItem->getPrice());
        self::assertNotSame($productLineItemPrice->getPrice(), $lineItem->getPrice());
        self::assertEquals($productLineItemPrice->getPrice()->getValue(), $lineItem->getValue());
        self::assertEquals($productLineItemPrice->getPrice()->getCurrency(), $lineItem->getCurrency());
    }

    public function testAddMatchingPricesWhenProductKitLineItemPriceWithoutKitItemLineItem(): void
    {
        $order = (new Order())
            ->setCurrency('USD');
        $productKit = (new Product())
            ->setType(Product::TYPE_KIT);

        $kitItemLineItem = new OrderProductKitItemLineItem();
        $lineItem = (new OrderLineItem())
            ->setProduct($productKit)
            ->addKitItemLineItem($kitItemLineItem);
        $order->addLineItem($lineItem);

        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with($order)
            ->willReturn($priceScopeCriteria);

        $productKitLineItemPrice = new ProductKitLineItemPrice($lineItem, Price::create(34.5678, 'USD'), 34.5678);
        $this->productLineItemPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([$lineItem], $priceScopeCriteria, $order->getCurrency())
            ->willReturn([$productKitLineItemPrice]);

        $this->matcher->addMatchingPrices($order);

        self::assertEquals($productKitLineItemPrice->getPrice(), $lineItem->getPrice());
        self::assertNotSame($productKitLineItemPrice->getPrice(), $lineItem->getPrice());
        self::assertEquals($productKitLineItemPrice->getPrice()->getValue(), $lineItem->getValue());
        self::assertEquals($productKitLineItemPrice->getPrice()->getCurrency(), $lineItem->getCurrency());

        self::assertNull($kitItemLineItem->getPrice());
        self::assertNull($kitItemLineItem->getValue());
        self::assertNull($kitItemLineItem->getCurrency());
    }

    public function testAddMatchingPricesWhenProductKitLineItemPriceWithKitItemLineItem(): void
    {
        $order = (new Order())
            ->setCurrency('USD');
        $productKit = (new Product())
            ->setType(Product::TYPE_KIT);

        $kitItemLineItem = new OrderProductKitItemLineItem();
        $lineItem = (new OrderLineItem())
            ->setProduct($productKit)
            ->addKitItemLineItem($kitItemLineItem);
        $order->addLineItem($lineItem);

        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with($order)
            ->willReturn($priceScopeCriteria);

        $productKitItemLineItemPrice = new ProductKitItemLineItemPrice(
            $kitItemLineItem,
            Price::create(12.3456, 'USD'),
            12.3456
        );
        $productKitLineItemPrice = (new ProductKitLineItemPrice($lineItem, Price::create(34.5678, 'USD'), 34.5678))
            ->addKitItemLineItemPrice($productKitItemLineItemPrice);
        $this->productLineItemPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([$lineItem], $priceScopeCriteria, $order->getCurrency())
            ->willReturn([$productKitLineItemPrice]);

        $this->matcher->addMatchingPrices($order);

        self::assertEquals($productKitLineItemPrice->getPrice(), $lineItem->getPrice());
        self::assertNotSame($productKitLineItemPrice->getPrice(), $lineItem->getPrice());
        self::assertEquals($productKitLineItemPrice->getPrice()->getValue(), $lineItem->getValue());
        self::assertEquals($productKitLineItemPrice->getPrice()->getCurrency(), $lineItem->getCurrency());

        self::assertEquals($productKitItemLineItemPrice->getPrice(), $kitItemLineItem->getPrice());
        self::assertNotSame($productKitItemLineItemPrice->getPrice(), $kitItemLineItem->getPrice());
        self::assertEquals($productKitItemLineItemPrice->getPrice()->getValue(), $kitItemLineItem->getValue());
        self::assertEquals($productKitItemLineItemPrice->getPrice()->getCurrency(), $kitItemLineItem->getCurrency());
    }
}
