<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\OrderBundle\Provider\OrderProductPriceProvider;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderProductPriceProviderTest extends TestCase
{
    private ProductPriceProviderInterface|MockObject $productPriceProvider;

    private ProductPriceScopeCriteriaFactoryInterface|MockObject $priceScopeCriteriaFactory;

    private OrderProductPriceProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);
        $this->priceScopeCriteriaFactory = $this->createMock(ProductPriceScopeCriteriaFactoryInterface::class);

        $this->provider = new OrderProductPriceProvider($this->productPriceProvider, $this->priceScopeCriteriaFactory);
    }

    public function testGetProductPricesWhenNoLineItems(): void
    {
        $this->productPriceProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->priceScopeCriteriaFactory
            ->expects(self::never())
            ->method(self::anything());

        self::assertSame([], $this->provider->getProductPrices(new Order()));
    }

    public function testGetProductPricesWhenNoLineItemsWithProducts(): void
    {
        $this->productPriceProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->priceScopeCriteriaFactory
            ->expects(self::never())
            ->method(self::anything());

        self::assertSame([], $this->provider->getProductPrices((new Order())->addLineItem(new OrderLineItem())));
    }

    public function testGetProductPricesWhenHasLineItemWithProduct(): void
    {
        $product = (new ProductStub())->setId(10);
        $lineItem = (new OrderLineItem())
            ->setProduct($product);
        $order = (new Order())
            ->addLineItem($lineItem)
            ->setCurrency('USD');

        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with($order)
            ->willReturn($priceScopeCriteria);

        $productPrice1 = $this->createMock(ProductPriceInterface::class);
        $productPrice2 = $this->createMock(ProductPriceInterface::class);
        $productPrices = [$product->getId() => [$productPrice1, $productPrice2]];
        $this->productPriceProvider
            ->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with($priceScopeCriteria, [$product], [$order->getCurrency()])
            ->willReturn($productPrices);

        self::assertSame($productPrices, $this->provider->getProductPrices($order));
    }

    public function testGetProductPricesWhenHasLineItemWithProductKitAndNoKitItemLineItems(): void
    {
        $kitItem1Product1 = (new ProductStub())->setId(100);
        $kitItem1Product2 = (new ProductStub())->setId(101);
        $kitItem1 = (new ProductKitItemStub())
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem1Product1))
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem1Product2));
        $kitItem2Product1 = (new ProductStub())->setId(200);
        $kitItem2 = (new ProductKitItemStub())
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem2Product1));
        $productKit = (new ProductStub())
            ->setId(10)
            ->setType(Product::TYPE_KIT)
            ->addKitItem($kitItem1)
            ->addKitItem($kitItem2);
        $lineItem = (new OrderLineItem())
            ->setProduct($productKit);
        $order = (new Order())
            ->addLineItem($lineItem)
            ->setCurrency('USD');

        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with($order)
            ->willReturn($priceScopeCriteria);

        $productPrice1 = $this->createMock(ProductPriceInterface::class);
        $productPrice2 = $this->createMock(ProductPriceInterface::class);
        $productPrices = [$productKit->getId() => [$productPrice1, $productPrice2]];
        $this->productPriceProvider
            ->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with(
                $priceScopeCriteria,
                [$productKit, $kitItem1Product1, $kitItem1Product2, $kitItem2Product1],
                [$order->getCurrency()]
            )
            ->willReturn($productPrices);

        self::assertSame($productPrices, $this->provider->getProductPrices($order));
    }

    public function testGetProductPricesWhenHasLineItemWithProductKitAndKitItemLineItem(): void
    {
        $kitItem1Product1 = (new ProductStub())->setId(100);
        $kitItem1Product2 = (new ProductStub())->setId(101);
        $kitItem1 = (new ProductKitItemStub())
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem1Product1))
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem1Product2));
        $kitItem2Product1 = (new ProductStub())->setId(200);
        $kitItem2 = (new ProductKitItemStub())
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem2Product1));
        $productKit = (new ProductStub())
            ->setId(10)
            ->setType(Product::TYPE_KIT)
            ->addKitItem($kitItem1)
            ->addKitItem($kitItem2);
        $kitItemLineItem1Product = new Product();
        $kitItemLineItem1 = (new OrderProductKitItemLineItem())
            ->setProduct($kitItemLineItem1Product);
        $lineItem = (new OrderLineItem())
            ->setProduct($productKit)
            ->addKitItemLineItem($kitItemLineItem1);
        $order = (new Order())
            ->addLineItem($lineItem)
            ->setCurrency('USD');

        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with($order)
            ->willReturn($priceScopeCriteria);

        $productPrice1 = $this->createMock(ProductPriceInterface::class);
        $productPrice2 = $this->createMock(ProductPriceInterface::class);
        $productPrices = [$productKit->getId() => [$productPrice1, $productPrice2]];
        $this->productPriceProvider
            ->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with(
                $priceScopeCriteria,
                [$productKit, $kitItemLineItem1Product, $kitItem1Product1, $kitItem1Product2, $kitItem2Product1],
                [$order->getCurrency()]
            )
            ->willReturn($productPrices);

        self::assertSame($productPrices, $this->provider->getProductPrices($order));
    }

    public function testGetProductPricesWhenHasLineItemWithProductKitAndNoKitItemLineItemProduct(): void
    {
        $kitItem1Product1 = (new ProductStub())->setId(100);
        $kitItem1Product2 = (new ProductStub())->setId(101);
        $kitItem1 = (new ProductKitItemStub())
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem1Product1))
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem1Product2));
        $kitItem2Product1 = (new ProductStub())->setId(200);
        $kitItem2 = (new ProductKitItemStub())
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem2Product1));
        $productKit = (new ProductStub())
            ->setId(10)
            ->setType(Product::TYPE_KIT)
            ->addKitItem($kitItem1)
            ->addKitItem($kitItem2);
        $kitItemLineItem1 = new OrderProductKitItemLineItem();
        $lineItem = (new OrderLineItem())
            ->setProduct($productKit)
            ->addKitItemLineItem($kitItemLineItem1);
        $order = (new Order())
            ->addLineItem($lineItem)
            ->setCurrency('USD');

        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with($order)
            ->willReturn($priceScopeCriteria);

        $productPrice1 = $this->createMock(ProductPriceInterface::class);
        $productPrice2 = $this->createMock(ProductPriceInterface::class);
        $productPrices = [$productKit->getId() => [$productPrice1, $productPrice2]];
        $this->productPriceProvider
            ->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with(
                $priceScopeCriteria,
                [$productKit, $kitItem1Product1, $kitItem1Product2, $kitItem2Product1],
                [$order->getCurrency()]
            )
            ->willReturn($productPrices);

        self::assertSame($productPrices, $this->provider->getProductPrices($order));
    }
}
