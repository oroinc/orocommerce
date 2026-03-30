<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\OrderBundle\Provider\OrderLineItemTierPricesProvider;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemProductPriceProviderInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderLineItemTierPricesProviderTest extends TestCase
{
    private ProductPriceProviderInterface&MockObject $productPriceProvider;
    private ProductPriceScopeCriteriaFactoryInterface&MockObject $priceScopeCriteriaFactory;
    private ProductLineItemProductPriceProviderInterface&MockObject $productLineItemProductPriceProvider;
    private OrderLineItemTierPricesProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);
        $this->priceScopeCriteriaFactory = $this->createMock(ProductPriceScopeCriteriaFactoryInterface::class);
        $this->productLineItemProductPriceProvider = $this->createMock(
            ProductLineItemProductPriceProviderInterface::class
        );

        $this->provider = new OrderLineItemTierPricesProvider(
            $this->productPriceProvider,
            $this->priceScopeCriteriaFactory,
            $this->productLineItemProductPriceProvider
        );
    }

    public function testGetTierPricesForLineItemWhenNoOrder(): void
    {
        $lineItem = new OrderLineItem();

        $this->priceScopeCriteriaFactory
            ->expects(self::never())
            ->method('createByContext');

        $this->productPriceProvider
            ->expects(self::never())
            ->method('getPricesByScopeCriteriaAndProducts');

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

        $this->priceScopeCriteriaFactory
            ->expects(self::never())
            ->method('createByContext');

        $this->productPriceProvider
            ->expects(self::never())
            ->method('getPricesByScopeCriteriaAndProducts');

        $this->productLineItemProductPriceProvider
            ->expects(self::never())
            ->method('getProductLineItemProductPrices');

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

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with(self::identicalTo($order))
            ->willReturn($scopeCriteria);

        $productPrice1 = $this->createMock(ProductPriceDTO::class);
        $productPrice2 = $this->createMock(ProductPriceDTO::class);
        $productPricesByProduct = [
            1 => [$productPrice1, $productPrice2],
        ];

        $this->productPriceProvider
            ->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with(
                self::identicalTo($scopeCriteria),
                self::equalTo([$product]),
                self::equalTo(['USD'])
            )
            ->willReturn($productPricesByProduct);

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

        self::assertArrayHasKey(1, $result);
        self::assertSame($expectedPrices, $result[1]);
    }

    public function testGetTierPricesForProductKit(): void
    {
        $product = new Product();
        ReflectionUtil::setId($product, 1);
        $product->setType(Product::TYPE_KIT);

        $kitItemProduct1 = new Product();
        ReflectionUtil::setId($kitItemProduct1, 2);
        $kitItemProduct1->setType(Product::TYPE_SIMPLE);

        $kitItemProduct2 = new Product();
        ReflectionUtil::setId($kitItemProduct2, 3);
        $kitItemProduct2->setType(Product::TYPE_SIMPLE);

        $kitItem1 = new ProductKitItem();
        ReflectionUtil::setId($kitItem1, 1);
        $kitItem1->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItemProduct1));

        $kitItem2 = new ProductKitItem();
        ReflectionUtil::setId($kitItem2, 2);
        $kitItem2->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItemProduct2));

        $product->addKitItem($kitItem1);
        $product->addKitItem($kitItem2);

        $order = new Order();
        $order->setCurrency('EUR');

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->addOrder($order);

        $kitItemLineItem1 = new OrderProductKitItemLineItem();
        $kitItemLineItem1->setProduct($kitItemProduct1);
        $kitItemLineItem1->setKitItemId(1);
        $kitItemLineItem1->setProductUnit(new ProductUnit());

        $kitItemLineItem2 = new OrderProductKitItemLineItem();
        $kitItemLineItem2->setProduct($kitItemProduct2);
        $kitItemLineItem2->setKitItemId(2);
        $kitItemLineItem2->setProductUnit(new ProductUnit());

        $lineItem->addKitItemLineItem($kitItemLineItem1);
        $lineItem->addKitItemLineItem($kitItemLineItem2);

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with(self::identicalTo($order))
            ->willReturn($scopeCriteria);

        $productPricesByProduct = [
            1 => [$this->createMock(ProductPriceDTO::class)],
            2 => [$this->createMock(ProductPriceDTO::class)],
            3 => [$this->createMock(ProductPriceDTO::class)],
        ];

        $this->productPriceProvider
            ->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with(
                self::identicalTo($scopeCriteria),
                self::equalTo([$product, $kitItemProduct1, $kitItemProduct2]),
                self::equalTo(['EUR'])
            )
            ->willReturn($productPricesByProduct);

        $expectedPrices = [$this->createMock(ProductPriceDTO::class)];

        $this->productLineItemProductPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemProductPrices')
            ->with(
                self::identicalTo($lineItem),
                self::callback(function ($arg) {
                    return $arg instanceof ProductPriceCollectionDTO && count($arg) === 3;
                }),
                self::equalTo('EUR')
            )
            ->willReturn($expectedPrices);

        $result = $this->provider->getTierPricesForLineItem($lineItem);

        self::assertArrayHasKey(1, $result);
        self::assertSame($expectedPrices, $result[1]);
    }

    public function testGetTierPricesForProductKitWithoutKitItemLineItems(): void
    {
        $product = new Product();
        ReflectionUtil::setId($product, 1);
        $product->setType(Product::TYPE_KIT);

        $kitItemProduct = new Product();
        ReflectionUtil::setId($kitItemProduct, 2);
        $kitItemProduct->setType(Product::TYPE_SIMPLE);

        $kitItem = new ProductKitItem();
        ReflectionUtil::setId($kitItem, 1);
        $kitItem->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItemProduct));

        $product->addKitItem($kitItem);

        $order = new Order();
        $order->setCurrency('USD');

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->addOrder($order);

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with(self::identicalTo($order))
            ->willReturn($scopeCriteria);

        $this->productPriceProvider
            ->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with(
                self::identicalTo($scopeCriteria),
                self::equalTo([$product, $kitItemProduct]),
                self::equalTo(['USD'])
            )
            ->willReturn([]);

        $this->productLineItemProductPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemProductPrices')
            ->with(
                self::identicalTo($lineItem),
                self::callback(function ($arg) {
                    return $arg instanceof ProductPriceCollectionDTO && count($arg) === 0;
                }),
                self::equalTo('USD')
            )
            ->willReturn([]);

        $result = $this->provider->getTierPricesForLineItem($lineItem);

        self::assertSame([1 => []], $result);
    }

    public function testGetTierPricesForProductKitWithNullProductInKitItemLineItem(): void
    {
        $product = new Product();
        ReflectionUtil::setId($product, 1);
        $product->setType(Product::TYPE_KIT);

        $kitItemProduct = new Product();
        ReflectionUtil::setId($kitItemProduct, 2);

        $kitItem = new ProductKitItem();
        ReflectionUtil::setId($kitItem, 1);
        $kitItem->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItemProduct));

        $product->addKitItem($kitItem);

        $order = new Order();
        $order->setCurrency('USD');

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->addOrder($order);

        // Kit item line item with null product
        $kitItemLineItem = new OrderProductKitItemLineItem();
        $kitItemLineItem->setKitItemId(1);
        $kitItemLineItem->setProductUnit(new ProductUnit());
        $lineItem->addKitItemLineItem($kitItemLineItem);

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with(self::identicalTo($order))
            ->willReturn($scopeCriteria);

        $this->productPriceProvider
            ->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with(
                self::identicalTo($scopeCriteria),
                self::equalTo([$product, $kitItemProduct]),
                self::equalTo(['USD'])
            )
            ->willReturn([]);

        $this->productLineItemProductPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemProductPrices')
            ->willReturn([]);

        $result = $this->provider->getTierPricesForLineItem($lineItem);

        self::assertSame([1 => []], $result);
    }

    public function testGetTierPricesForProductKitWithMultipleCurrencies(): void
    {
        $product = new Product();
        ReflectionUtil::setId($product, 10);
        $product->setType(Product::TYPE_KIT);

        $order = new Order();
        $order->setCurrency('GBP');

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->addOrder($order);

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with(self::identicalTo($order))
            ->willReturn($scopeCriteria);

        $this->productPriceProvider
            ->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with(
                self::identicalTo($scopeCriteria),
                self::equalTo([$product]),
                self::equalTo(['GBP'])
            )
            ->willReturn([10 => []]);

        $this->productLineItemProductPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemProductPrices')
            ->with(
                self::identicalTo($lineItem),
                self::isInstanceOf(ProductPriceCollectionDTO::class),
                self::equalTo('GBP')
            )
            ->willReturn([]);

        $result = $this->provider->getTierPricesForLineItem($lineItem);

        self::assertArrayHasKey(10, $result);
    }
}
