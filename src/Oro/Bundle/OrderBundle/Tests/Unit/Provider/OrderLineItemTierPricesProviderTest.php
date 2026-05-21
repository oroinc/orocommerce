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

        self::assertSame([1 => $expectedPrices], $result);
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
        self::assertArrayHasKey(2, $result);
        self::assertArrayHasKey(3, $result);
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

        self::assertSame([10 => []], $result);
    }

    // -------------------------------------------------------------------------
    // getTierPricesForLineItems tests
    // -------------------------------------------------------------------------
    public function testGetTierPricesForLineItemsWhenEmptyInput(): void
    {
        $this->productPriceProvider
            ->expects(self::never())
            ->method('getPricesByScopeCriteriaAndProducts');

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

        $this->priceScopeCriteriaFactory
            ->expects(self::never())
            ->method('createByContext');

        $this->productPriceProvider
            ->expects(self::never())
            ->method('getPricesByScopeCriteriaAndProducts');

        $result = $this->provider->getTierPricesForLineItems(['a' => $lineItem]);

        self::assertSame(['a' => []], $result);
    }

    public function testGetTierPricesForLineItemsWhenNoProducts(): void
    {
        $order = new Order();
        $order->setCurrency('USD');

        $lineItem = new OrderLineItem();
        $lineItem->addOrder($order);
        // no product on line item

        $this->productPriceProvider
            ->expects(self::never())
            ->method('getPricesByScopeCriteriaAndProducts');

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

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with(self::identicalTo($order))
            ->willReturn($scopeCriteria);

        $price10a = $this->createMock(ProductPriceDTO::class);
        $price10b = $this->createMock(ProductPriceDTO::class);
        $price20a = $this->createMock(ProductPriceDTO::class);

        $this->productPriceProvider
            ->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with(
                self::identicalTo($scopeCriteria),
                self::callback(static function (array $products) use ($product1, $product2) {
                    return \count($products) === 2
                        && \in_array($product1, $products, true)
                        && \in_array($product2, $products, true);
                }),
                self::equalTo(['USD'])
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

    public function testGetTierPricesForLineItemsDeduplicatesSameProduct(): void
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

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->willReturn($scopeCriteria);

        $price = $this->createMock(ProductPriceDTO::class);

        $this->productPriceProvider
            ->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with(
                self::anything(),
                self::callback(static fn (array $p) => \count($p) === 1),
                self::equalTo(['EUR'])
            )
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

        $kitItemProduct = new Product();
        ReflectionUtil::setId($kitItemProduct, 2);
        $kitItemProduct->setType(Product::TYPE_SIMPLE);

        $kitItem = new ProductKitItem();
        ReflectionUtil::setId($kitItem, 1);
        $kitItem->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItemProduct));
        $kitProduct->addKitItem($kitItem);

        $order = new Order();
        $order->setCurrency('USD');

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($kitProduct);
        $lineItem->addOrder($order);

        $kitItemLineItem = new OrderProductKitItemLineItem();
        $kitItemLineItem->setProduct($kitItemProduct);
        $kitItemLineItem->setKitItemId(1);
        $kitItemLineItem->setProductUnit(new ProductUnit());
        $lineItem->addKitItemLineItem($kitItemLineItem);

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->willReturn($scopeCriteria);

        $priceKit = $this->createMock(ProductPriceDTO::class);
        $priceItem = $this->createMock(ProductPriceDTO::class);

        $this->productPriceProvider
            ->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->willReturn([1 => [$priceKit], 2 => [$priceItem]]);

        $matchedPrices = [$this->createMock(ProductPriceDTO::class)];

        $this->productLineItemProductPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemProductPrices')
            ->with(
                self::identicalTo($lineItem),
                self::callback(static fn ($c) => $c instanceof ProductPriceCollectionDTO && \count($c) === 2),
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

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->willReturn($scopeCriteria);

        $simplePrice = $this->createMock(ProductPriceDTO::class);
        $kitPrice = $this->createMock(ProductPriceDTO::class);

        $this->productPriceProvider
            ->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
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
