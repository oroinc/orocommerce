<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\ProductKitPriceCriteria;
use Oro\Bundle\PricingBundle\Provider\PriceByMatchingCriteria\ProductPriceByMatchingCriteriaProviderInterface;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemProductPriceProvider;
use Oro\Bundle\PricingBundle\Tests\Unit\Stub\ProductKitItemLineItemPriceAwareStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemsAwareStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductLineItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductLineItemProductPriceProviderTest extends TestCase
{
    private ProductPriceByMatchingCriteriaProviderInterface|MockObject $productPriceByMatchingCriteriaProvider;

    private ProductPriceCriteriaFactoryInterface|MockObject $productPriceCriteriaFactory;

    private ProductLineItemProductPriceProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->productPriceByMatchingCriteriaProvider = $this
            ->createMock(ProductPriceByMatchingCriteriaProviderInterface::class);
        $this->productPriceCriteriaFactory = $this->createMock(ProductPriceCriteriaFactoryInterface::class);

        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService
            ->method('round')
            ->willReturnCallback(static fn ($value) => round($value, 2));

        $this->provider = new ProductLineItemProductPriceProvider(
            $this->productPriceCriteriaFactory,
            $this->productPriceByMatchingCriteriaProvider,
            $roundingService
        );
    }

    public function testGetProductLineItemProductPricesWhenNoProduct(): void
    {
        self::assertSame(
            [],
            $this->provider->getProductLineItemProductPrices(
                new ProductLineItemStub(42),
                new ProductPriceCollectionDTO(),
                'USD'
            )
        );
    }

    public function testGetProductLineItemProductPricesWhenNoProductUnit(): void
    {
        $product = (new ProductStub())->setId(42);

        self::assertSame(
            [],
            $this->provider->getProductLineItemProductPrices(
                (new ProductLineItemStub(42))->setProduct($product),
                new ProductPriceCollectionDTO(),
                'USD'
            )
        );
    }

    public function testGetProductLineItemProductPricesWhenNotProductKitAndNoPrices(): void
    {
        $unitItem = (new ProductUnit())->setCode('item');
        $product = (new ProductStub())
            ->setId(42)
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unitItem));

        self::assertSame(
            [],
            $this->provider->getProductLineItemProductPrices(
                (new ProductLineItemStub(42))->setProduct($product)->setUnit($unitItem),
                new ProductPriceCollectionDTO(),
                'USD'
            )
        );
    }

    public function testGetProductLineItemProductPricesWhenNotProductKitAndHasMatchingPrices(): void
    {
        $unitItem = (new ProductUnit())->setCode('item');
        $product = (new ProductStub())
            ->setId(42)
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unitItem));
        $productPriceUsd1 = new ProductPriceDTO($product, Price::create(12.3456, 'USD'), 1.0, $unitItem);
        $productPriceUsd10 = new ProductPriceDTO($product, Price::create(12.3456, 'USD'), 10.0, $unitItem);
        $productPriceEur1 = new ProductPriceDTO($product, Price::create(12.3456, 'EUR'), 1.0, $unitItem);

        self::assertSame(
            [$productPriceUsd1, $productPriceUsd10],
            $this->provider->getProductLineItemProductPrices(
                (new ProductLineItemStub(42))->setProduct($product)->setUnit($unitItem),
                new ProductPriceCollectionDTO([$productPriceUsd1, $productPriceUsd10, $productPriceEur1]),
                'USD'
            )
        );
    }

    public function testGetProductLineItemProductPricesWhenNotProductKitAndNoMatchingPrices(): void
    {
        $unitItem = (new ProductUnit())->setCode('item');
        $unitKg = (new ProductUnit())->setCode('kg');
        $product = (new ProductStub())
            ->setId(42)
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unitKg));
        $productPriceUsd1 = new ProductPriceDTO($product, Price::create(12.3456, 'USD'), 1.0, $unitItem);
        $productPriceUsd10 = new ProductPriceDTO($product, Price::create(12.3456, 'USD'), 10.0, $unitItem);
        $productPriceEur1 = new ProductPriceDTO($product, Price::create(12.3456, 'EUR'), 1.0, $unitItem);

        self::assertSame(
            [],
            $this->provider->getProductLineItemProductPrices(
                (new ProductLineItemStub(42))->setProduct($product)->setUnit($unitKg),
                new ProductPriceCollectionDTO([$productPriceUsd1, $productPriceUsd10, $productPriceEur1]),
                'USD'
            )
        );
    }

    public function testGetProductLineItemProductPricesWhenIsProductKitAndNoKitItemLineItems(): void
    {
        $productPriceCollection = new ProductPriceCollectionDTO();
        $unitItem = (new ProductUnit())->setCode('item');
        $product = (new ProductStub())
            ->setId(42)
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unitItem))
            ->setType(Product::TYPE_KIT);
        $lineItem = (new ProductLineItemStub(42))
            ->setProduct($product)
            ->setUnit($unitItem)
            ->setQuantity(23.4567);

        $this->productPriceCriteriaFactory
            ->expects(self::never())
            ->method(self::anything());

        $this->productPriceByMatchingCriteriaProvider
            ->expects(self::never())
            ->method(self::anything());

        self::assertSame(
            [],
            $this->provider->getProductLineItemProductPrices(
                $lineItem,
                $productPriceCollection,
                'USD'
            )
        );
    }

    public function testGetProductLineItemProductPricesWhenIsProductKitAndNoValidKitItemLineItem(): void
    {
        $productPriceCollection = new ProductPriceCollectionDTO();
        $unitItem = (new ProductUnit())->setCode('item');
        $kitItem1Product = (new ProductStub())->setId(100);
        $kitItem1 = (new ProductKitItemStub(10))
            ->setProductUnit($unitItem)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem1Product));
        $product = (new ProductStub())
            ->setId(42)
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unitItem))
            ->setType(Product::TYPE_KIT)
            ->addKitItem($kitItem1);
        $kitItemLineItem1 = (new ProductKitItemLineItemStub(1000))
            ->setKitItem($kitItem1)
            ->setQuantity(1.2345);
        $lineItem = (new ProductKitItemLineItemsAwareStub(42))
            ->setProduct($product)
            ->setUnit($unitItem)
            ->setQuantity(23.4567)
            ->addKitItemLineItem($kitItemLineItem1);

        $this->productPriceCriteriaFactory
            ->expects(self::never())
            ->method(self::anything());

        $this->productPriceByMatchingCriteriaProvider
            ->expects(self::never())
            ->method(self::anything());

        $productPrice = new ProductPriceDTO(
            $lineItem->getProduct(),
            Price::create(0.0, 'USD'),
            1.0,
            $lineItem->getUnit()
        );

        self::assertEquals(
            [$productPrice],
            $this->provider->getProductLineItemProductPrices(
                $lineItem,
                $productPriceCollection,
                'USD'
            )
        );
    }

    public function testGetProductLineItemProductPricesWhenIsProductKitAndNoCriteriaForKitItemLineItem(): void
    {
        $productPriceCollection = new ProductPriceCollectionDTO();
        $unitItem = (new ProductUnit())->setCode('item');
        $kitItem1Product = (new ProductStub())->setId(100);
        $kitItem1 = (new ProductKitItemStub(10))
            ->setProductUnit($unitItem)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem1Product));
        $product = (new ProductStub())
            ->setId(42)
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unitItem))
            ->setType(Product::TYPE_KIT)
            ->addKitItem($kitItem1);
        $kitItemLineItem1 = (new ProductKitItemLineItemStub(1000))
            ->setKitItem($kitItem1)
            ->setProduct($kitItem1Product)
            ->setUnit($unitItem)
            ->setQuantity(1.2345);
        $lineItem = (new ProductKitItemLineItemsAwareStub(42))
            ->setProduct($product)
            ->setUnit($unitItem)
            ->setQuantity(23.4567)
            ->addKitItemLineItem($kitItemLineItem1);

        $this->productPriceCriteriaFactory
            ->expects(self::once())
            ->method('createFromProductLineItem')
            ->with($kitItemLineItem1)
            ->willReturn(null);

        $this->productPriceByMatchingCriteriaProvider
            ->expects(self::never())
            ->method(self::anything());

        $productPrice = new ProductPriceDTO(
            $lineItem->getProduct(),
            Price::create(0.0, 'USD'),
            1.0,
            $lineItem->getUnit()
        );

        self::assertEquals(
            [$productPrice],
            $this->provider->getProductLineItemProductPrices(
                $lineItem,
                $productPriceCollection,
                'USD'
            )
        );
    }

    public function testGetProductLineItemProductPricesWhenIsProductKitAndNoPrices(): void
    {
        $productPriceCollection = new ProductPriceCollectionDTO();
        $unitItem = (new ProductUnit())->setCode('item');
        $kitItem1Product = (new ProductStub())->setId(100);
        $kitItem1 = (new ProductKitItemStub(10))
            ->setProductUnit($unitItem)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem1Product));
        $product = (new ProductStub())
            ->setId(42)
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unitItem))
            ->setType(Product::TYPE_KIT)
            ->addKitItem($kitItem1);
        $kitItemLineItem1 = (new ProductKitItemLineItemStub(1000))
            ->setKitItem($kitItem1)
            ->setProduct($kitItem1Product)
            ->setUnit($unitItem)
            ->setQuantity(1.2345);
        $lineItem = (new ProductKitItemLineItemsAwareStub(42))
            ->setProduct($product)
            ->setUnit($unitItem)
            ->setQuantity(23.4567)
            ->addKitItemLineItem($kitItemLineItem1);

        $productPriceCriteria = $this->createMock(ProductKitPriceCriteria::class);
        $this->productPriceCriteriaFactory
            ->expects(self::once())
            ->method('createFromProductLineItem')
            ->with($kitItemLineItem1)
            ->willReturn($productPriceCriteria);

        $this->productPriceByMatchingCriteriaProvider
            ->expects(self::once())
            ->method('getProductPriceMatchingCriteria')
            ->with($productPriceCriteria, $productPriceCollection)
            ->willReturn(null);

        $productPrice = new ProductPriceDTO(
            $lineItem->getProduct(),
            Price::create(0.0, 'USD'),
            1.0,
            $lineItem->getUnit()
        );

        self::assertEquals(
            [$productPrice],
            $this->provider->getProductLineItemProductPrices(
                $lineItem,
                $productPriceCollection,
                'USD'
            )
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetProductLineItemProductPricesWhenIsProductKitAndHasPrices(): void
    {
        $unitItem = (new ProductUnit())->setCode('item');
        $unitKg = (new ProductUnit())->setCode('kg');
        $kitItem1Product = (new ProductStub())->setId(100);
        $kitItem1 = (new ProductKitItemStub(10))
            ->setProductUnit($unitItem)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem1Product));
        $kitItem2Product = (new ProductStub())->setId(200);
        $kitItem2 = (new ProductKitItemStub(10))
            ->setProductUnit($unitKg)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem2Product));
        $product = (new ProductStub())
            ->setId(42)
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unitItem))
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unitKg))
            ->setType(Product::TYPE_KIT)
            ->addKitItem($kitItem1)
            ->addKitItem($kitItem2);

        $kitItem1ProductPriceUsd1 = new ProductPriceDTO(
            $kitItem1Product,
            Price::create(11.2345, 'USD'),
            1.0,
            $unitItem
        );
        $kitItem2ProductPriceUsd1 = new ProductPriceDTO($kitItem2Product, Price::create(22.3456, 'USD'), 1.0, $unitKg);
        $productPriceUsd1 = new ProductPriceDTO($product, Price::create(12.3456, 'USD'), 1.0, $unitItem);
        $productPriceUsd10 = new ProductPriceDTO($product, Price::create(10.3456, 'USD'), 10.0, $unitItem);
        $productPriceEur1 = new ProductPriceDTO($product, Price::create(7.3456, 'EUR'), 1.0, $unitItem);
        $productPriceCollection = new ProductPriceCollectionDTO(
            [
                $productPriceUsd1,
                $productPriceUsd10,
                $productPriceEur1,
                $kitItem1ProductPriceUsd1,
                $kitItem2ProductPriceUsd1,
            ]
        );

        $kitItemLineItem1 = (new ProductKitItemLineItemStub(1000))
            ->setKitItem($kitItem1)
            ->setProduct($kitItem1Product)
            ->setUnit($unitItem)
            ->setQuantity(1.2345);
        $kitItemLineItem2 = (new ProductKitItemLineItemStub(2000))
            ->setKitItem($kitItem2)
            ->setProduct($kitItem2Product)
            ->setUnit($unitKg)
            ->setQuantity(2.3456);
        $lineItem = (new ProductKitItemLineItemsAwareStub(42))
            ->setProduct($product)
            ->setUnit($unitItem)
            ->setQuantity(23.4567)
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);

        $productPriceCriteria1 = $this->createMock(ProductKitPriceCriteria::class);
        $productPriceCriteria2 = $this->createMock(ProductKitPriceCriteria::class);
        $this->productPriceCriteriaFactory
            ->expects(self::exactly(2))
            ->method('createFromProductLineItem')
            ->willReturnMap([
                [$kitItemLineItem1, 'USD', $productPriceCriteria1],
                [$kitItemLineItem2, 'USD', $productPriceCriteria2],
            ]);

        $this->productPriceByMatchingCriteriaProvider
            ->expects(self::exactly(2))
            ->method('getProductPriceMatchingCriteria')
            ->willReturnMap([
                [$productPriceCriteria1, $productPriceCollection, $kitItem1ProductPriceUsd1],
                [$productPriceCriteria2, $productPriceCollection, $kitItem2ProductPriceUsd1],
            ]);

        $productPrice1 = new ProductPriceDTO(
            $lineItem->getProduct(),
            Price::create(78.6256, 'USD'),
            1.0,
            $lineItem->getUnit()
        );
        $productPrice2 = new ProductPriceDTO(
            $lineItem->getProduct(),
            Price::create(76.6256, 'USD'),
            10.0,
            $lineItem->getUnit()
        );
        $productPrice3 = new ProductPriceDTO(
            $lineItem->getProduct(),
            Price::create(66.28, 'USD'),
            1.0,
            $unitKg
        );

        self::assertEquals(
            [$productPrice1, $productPrice2, $productPrice3],
            $this->provider->getProductLineItemProductPrices($lineItem, $productPriceCollection, 'USD')
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetProductLineItemProductPricesWhenIsProductKitAndHasOverriddenPrices(): void
    {
        $unitItem = (new ProductUnit())->setCode('item');
        $unitKg = (new ProductUnit())->setCode('kg');
        $kitItem1Product = (new ProductStub())->setId(100);
        $kitItem1 = (new ProductKitItemStub(10))
            ->setProductUnit($unitItem)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem1Product));
        $kitItem2Product = (new ProductStub())->setId(200);
        $kitItem2 = (new ProductKitItemStub(10))
            ->setProductUnit($unitKg)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem2Product));
        $product = (new ProductStub())
            ->setId(42)
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unitItem))
            ->setType(Product::TYPE_KIT)
            ->addKitItem($kitItem1)
            ->addKitItem($kitItem2);

        $kitItem1ProductPriceUsd1 = new ProductPriceDTO($kitItem1Product, Price::create(1.2345, 'USD'), 1.0, $unitItem);
        $kitItem2ProductPriceUsd1 = new ProductPriceDTO($kitItem2Product, Price::create(2.3456, 'USD'), 1.0, $unitKg);
        $productPriceUsd1 = new ProductPriceDTO($product, Price::create(12.3456, 'USD'), 1.0, $unitItem);
        $productPriceUsd10 = new ProductPriceDTO($product, Price::create(10.3456, 'USD'), 10.0, $unitItem);
        $productPriceEur1 = new ProductPriceDTO($product, Price::create(7.3456, 'EUR'), 1.0, $unitItem);
        $productPriceCollection = new ProductPriceCollectionDTO(
            [
                $productPriceUsd1,
                $productPriceUsd10,
                $productPriceEur1,
                $kitItem1ProductPriceUsd1,
                $kitItem2ProductPriceUsd1,
            ]
        );

        $kitItemLineItem1 = (new ProductKitItemLineItemPriceAwareStub(1000))
            ->setKitItem($kitItem1)
            ->setProduct($kitItem1Product)
            ->setUnit($unitItem)
            ->setQuantity(1.2345)
            ->setPrice(Price::create(34.5678, 'USD'));
        $kitItemLineItem2 = (new ProductKitItemLineItemPriceAwareStub(2000))
            ->setKitItem($kitItem2)
            ->setProduct($kitItem2Product)
            ->setUnit($unitKg)
            ->setQuantity(2.3456)
            ->setPrice(Price::create(2346.7890, 'USD'));
        $lineItem = (new ProductKitItemLineItemsAwareStub(42))
            ->setProduct($product)
            ->setUnit($unitItem)
            ->setQuantity(23.4567)
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);

        $this->productPriceCriteriaFactory
            ->expects(self::never())
            ->method(self::anything());

        $productPrice1 = new ProductPriceDTO(
            $lineItem->getProduct(),
            Price::create(5559.6456, 'USD'),
            1.0,
            $lineItem->getUnit()
        );
        $productPrice2 = new ProductPriceDTO(
            $lineItem->getProduct(),
            Price::create(5557.6456, 'USD'),
            10.0,
            $lineItem->getUnit()
        );

        $this->productPriceByMatchingCriteriaProvider
            ->expects(self::never())
            ->method('getProductPriceMatchingCriteria');

        self::assertEquals(
            [$productPrice1, $productPrice2],
            $this->provider->getProductLineItemProductPrices($lineItem, $productPriceCollection, 'USD')
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetProductLineItemProductPricesWhenMultipleLineItemsAndOverriddenPrices(): void
    {
        $unitItem = (new ProductUnit())->setCode('item');
        $unitKg = (new ProductUnit())->setCode('kg');
        $kitItem1Product = (new ProductStub())->setId(100);
        $kitItem1 = (new ProductKitItemStub(10))
            ->setProductUnit($unitItem)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem1Product));
        $kitItem2Product = (new ProductStub())->setId(200);
        $kitItem2 = (new ProductKitItemStub(10))
            ->setProductUnit($unitKg)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem2Product));
        $product = (new ProductStub())
            ->setId(42)
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unitItem))
            ->setType(Product::TYPE_KIT)
            ->addKitItem($kitItem1)
            ->addKitItem($kitItem2);

        $kitItem1ProductPriceUsd1 = new ProductPriceDTO($kitItem1Product, Price::create(1.2345, 'USD'), 1.0, $unitItem);
        $kitItem2ProductPriceUsd1 = new ProductPriceDTO($kitItem2Product, Price::create(2.3456, 'USD'), 1.0, $unitKg);
        $productPriceUsd1 = new ProductPriceDTO($product, Price::create(12.3456, 'USD'), 1.0, $unitItem);
        $productPriceUsd10 = new ProductPriceDTO($product, Price::create(10.3456, 'USD'), 10.0, $unitItem);
        $productPriceEur1 = new ProductPriceDTO($product, Price::create(7.3456, 'EUR'), 1.0, $unitItem);
        $productPriceCollection = new ProductPriceCollectionDTO(
            [
                $productPriceUsd1,
                $productPriceUsd10,
                $productPriceEur1,
                $kitItem1ProductPriceUsd1,
                $kitItem2ProductPriceUsd1,
            ]
        );

        $lineItem1KitItem1 = (new ProductKitItemLineItemPriceAwareStub(1000))
            ->setKitItem($kitItem1)
            ->setProduct($kitItem1Product)
            ->setUnit($unitItem)
            ->setQuantity(1.2345)
            ->setPrice(Price::create(34.5678, 'USD'));
        $lineItem1KitItem2 = (new ProductKitItemLineItemPriceAwareStub(2000))
            ->setKitItem($kitItem2)
            ->setProduct($kitItem2Product)
            ->setUnit($unitKg)
            ->setQuantity(2.3456)
            ->setPrice(Price::create(2346.7890, 'USD'));
        $lineItem1 = (new ProductKitItemLineItemsAwareStub(42))
            ->setProduct($product)
            ->setUnit($unitItem)
            ->setQuantity(23.4567)
            ->addKitItemLineItem($lineItem1KitItem1)
            ->addKitItemLineItem($lineItem1KitItem2);

        $lineItem2KitItem1 = (new ProductKitItemLineItemPriceAwareStub(1000))
            ->setKitItem($kitItem1)
            ->setProduct($kitItem1Product)
            ->setUnit($unitItem)
            ->setQuantity(2.3456)
            ->setPrice(Price::create(45.6789, 'USD'));
        $lineItem2KitItem2 = (new ProductKitItemLineItemPriceAwareStub(2000))
            ->setKitItem($kitItem2)
            ->setProduct($kitItem2Product)
            ->setUnit($unitKg)
            ->setQuantity(3.4567)
            ->setPrice(Price::create(345.6789, 'USD'));
        $lineItem2 = (new ProductKitItemLineItemsAwareStub(42))
            ->setProduct($product)
            ->setUnit($unitItem)
            ->setQuantity(34.5678)
            ->addKitItemLineItem($lineItem2KitItem1)
            ->addKitItemLineItem($lineItem2KitItem2);

        $this->productPriceCriteriaFactory
            ->expects(self::never())
            ->method(self::anything());

        $productPrice1 = new ProductPriceDTO(
            $lineItem1->getProduct(),
            Price::create(5559.6456, 'USD'),
            1.0,
            $lineItem1->getUnit()
        );
        $productPrice2 = new ProductPriceDTO(
            $lineItem1->getProduct(),
            Price::create(5557.6456, 'USD'),
            10.0,
            $lineItem1->getUnit()
        );

        $this->productPriceByMatchingCriteriaProvider
            ->expects(self::never())
            ->method('getProductPriceMatchingCriteria');

        self::assertEquals(
            [$productPrice1, $productPrice2],
            $this->provider->getProductLineItemProductPrices($lineItem1, $productPriceCollection, 'USD')
        );

        $productPrice1 = new ProductPriceDTO(
            $lineItem1->getProduct(),
            Price::create(1314.3956, 'USD'),
            1.0,
            $lineItem1->getUnit()
        );
        $productPrice2 = new ProductPriceDTO(
            $lineItem1->getProduct(),
            Price::create(1312.3956, 'USD'),
            10.0,
            $lineItem1->getUnit()
        );
        self::assertEquals(
            [$productPrice1, $productPrice2],
            $this->provider->getProductLineItemProductPrices($lineItem2, $productPriceCollection, 'USD')
        );
    }
}
