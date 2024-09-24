<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\ProductKit\ProductLineItemPrice\Factory;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\Factory\ProductLineItemPriceFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\Factory\ProductKitLineItemPriceFactory;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitItemLineItemPrice;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitLineItemPrice;
use Oro\Bundle\PricingBundle\ProductKit\ProductPrice\ProductKitItemPriceDTO;
use Oro\Bundle\PricingBundle\ProductKit\ProductPrice\ProductKitPriceDTO;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemsAwareStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductKitLineItemPriceFactoryTest extends TestCase
{
    public const USD = 'USD';

    private ProductLineItemPriceFactoryInterface|MockObject $productLineItemPriceFactory;

    private ProductKitLineItemPriceFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->productLineItemPriceFactory = $this->createMock(ProductLineItemPriceFactoryInterface::class);
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService
            ->method('round')
            ->willReturnCallback(static fn ($value) => round($value, 2));

        $this->factory = new ProductKitLineItemPriceFactory($this->productLineItemPriceFactory, $roundingService);
    }

    /**
     * @dataProvider isSupportedDataProvider
     */
    public function testIsSupported(
        ProductLineItemInterface $lineItem,
        ProductPriceInterface $productPrice,
        bool $expected
    ): void {
        self::assertEquals($expected, $this->factory->isSupported($lineItem, $productPrice));
    }

    public function isSupportedDataProvider(): array
    {
        return [
            'not supported' => [
                'lineItem' => $this->createMock(ProductLineItemInterface::class),
                'productPrice' => $this->createMock(ProductPriceInterface::class),
                'expected' => false,
            ],
            'supported' => [
                'lineItem' => $this->createMock(LineItem::class),
                'productPrice' => $this->createMock(ProductKitPriceDTO::class),
                'expected' => true,
            ],
        ];
    }

    public function testCreateForProductLineItemWhenNotSupported(): void
    {
        self::assertNull(
            $this->factory->createForProductLineItem(
                $this->createMock(ProductLineItemInterface::class),
                $this->createMock(ProductPriceInterface::class)
            )
        );
    }

    public function testCreateForProductLineItemWhenSupported(): void
    {
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productUnitEach = (new ProductUnit())->setCode('each');
        $kitItemLineItem1Product = (new ProductStub())->setId(1);
        $kitItem1 = new ProductKitItemStub(11);
        $kitItemLineItem1 = (new ProductKitItemLineItemStub(10))
            ->setKitItem($kitItem1)
            ->setProduct($kitItemLineItem1Product)
            ->setUnit($productUnitItem)
            ->setQuantity(11);
        $kitItemLineItem2Product = (new ProductStub())->setId(2);
        $kitItem2 = new ProductKitItemStub(22);
        $kitItemLineItem2 = (new ProductKitItemLineItemStub(20))
            ->setKitItem($kitItem2)
            ->setProduct($kitItemLineItem2Product)
            ->setUnit($productUnitEach)
            ->setQuantity(22);
        $productKit = (new ProductStub())->setId(100)->setType(Product::TYPE_KIT);
        $kitLineItem = (new ProductKitItemLineItemsAwareStub(42))
            ->setProduct($productKit)
            ->setUnit($productUnitEach)
            ->setQuantity(111)
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);

        $kitItem1Price = new ProductKitItemPriceDTO(
            $kitItem1,
            $kitItemLineItem1Product,
            Price::create(1.234, self::USD),
            1,
            $productUnitItem
        );
        $kitItem2Price = new ProductKitItemPriceDTO(
            $kitItem2,
            $kitItemLineItem2Product,
            Price::create(2.345, self::USD),
            1,
            $productUnitItem
        );

        $kitItemLineItem1Price = new ProductKitItemLineItemPrice(
            $kitItemLineItem1,
            $kitItem1Price->getPrice(),
            $kitItem1Price->getPrice()->getValue() * $kitItemLineItem1->getQuantity()
        );
        $kitItemLineItem2Price = new ProductKitItemLineItemPrice(
            $kitItemLineItem2,
            $kitItem2Price->getPrice(),
            $kitItem2Price->getPrice()->getValue() * $kitItemLineItem2->getQuantity()
        );

        $productKitPrice = (new ProductKitPriceDTO(
            $productKit,
            Price::create(
                12.345 + $kitItemLineItem1Price->getSubtotal() + $kitItemLineItem2Price->getSubtotal(),
                self::USD
            ),
            1,
            $productUnitItem
        ))
            ->addKitItemPrice($kitItem1Price)
            ->addKitItemPrice($kitItem2Price);

        $this->productLineItemPriceFactory
            ->expects(self::exactly(2))
            ->method('createForProductLineItem')
            ->willReturnMap([
                [$kitItemLineItem1, $kitItem1Price, $kitItemLineItem1Price],
                [$kitItemLineItem2, $kitItem2Price, $kitItemLineItem2Price],
            ]);

        $kitLineItemPrice = (new ProductKitLineItemPrice($kitLineItem, Price::create(77.509, self::USD), 8603.5))
            ->addKitItemLineItemPrice($kitItemLineItem1Price)
            ->addKitItemLineItemPrice($kitItemLineItem2Price);

        self::assertEquals(
            $kitLineItemPrice,
            $this->factory->createForProductLineItem($kitLineItem, $productKitPrice)
        );
    }

    public function testCreateForProductLineItemWhenNoKitItemLineItemPrice(): void
    {
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productUnitEach = (new ProductUnit())->setCode('each');
        $kitItemLineItem1Product = (new ProductStub())->setId(1);
        $kitItem1 = new ProductKitItemStub(11);
        $kitItemLineItem1 = (new ProductKitItemLineItemStub(10))
            ->setKitItem($kitItem1)
            ->setProduct($kitItemLineItem1Product)
            ->setUnit($productUnitItem)
            ->setQuantity(11);
        $kitItemLineItem2Product = (new ProductStub())->setId(2);
        $kitItem2 = new ProductKitItemStub(22);
        $kitItemLineItem2 = (new ProductKitItemLineItemStub(20))
            ->setKitItem($kitItem2)
            ->setProduct($kitItemLineItem2Product)
            ->setUnit($productUnitEach)
            ->setQuantity(22);
        $productKit = (new ProductStub())->setId(100)->setType(Product::TYPE_KIT);
        $kitLineItem = (new ProductKitItemLineItemsAwareStub(42))
            ->setProduct($productKit)
            ->setUnit($productUnitEach)
            ->setQuantity(111)
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);

        $kitItem1Price = new ProductKitItemPriceDTO(
            $kitItem1,
            $kitItemLineItem1Product,
            Price::create(1.234, self::USD),
            1,
            $productUnitItem
        );
        $kitItem2Price = new ProductKitItemPriceDTO(
            $kitItem2,
            $kitItemLineItem2Product,
            Price::create(2.345, self::USD),
            1,
            $productUnitItem
        );

        $kitItemLineItem1Price = new ProductKitItemLineItemPrice(
            $kitItemLineItem1,
            $kitItem1Price->getPrice(),
            $kitItem1Price->getPrice()->getValue() * $kitItemLineItem1->getQuantity()
        );

        $productKitPrice = (new ProductKitPriceDTO(
            $productKit,
            Price::create(12.345 + $kitItemLineItem1Price->getSubtotal(), self::USD),
            1,
            $productUnitItem
        ))
            ->addKitItemPrice($kitItem1Price)
            ->addKitItemPrice($kitItem2Price);

        $this->productLineItemPriceFactory
            ->expects(self::exactly(2))
            ->method('createForProductLineItem')
            ->willReturnMap([
                [$kitItemLineItem1, $kitItem1Price, $kitItemLineItem1Price],
                [$kitItemLineItem2, $kitItem2Price, null],
            ]);

        $kitLineItemPrice = (new ProductKitLineItemPrice($kitLineItem, Price::create(25.919, self::USD), 2877.01))
            ->addKitItemLineItemPrice($kitItemLineItem1Price);

        self::assertEquals(
            $kitLineItemPrice,
            $this->factory->createForProductLineItem($kitLineItem, $productKitPrice)
        );
    }

    public function testCreateForProductLineItemWhenNoRequiredKitItemPrice(): void
    {
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productUnitEach = (new ProductUnit())->setCode('each');
        $kitItemLineItem1Product = (new ProductStub())->setId(1);
        $kitItem1 = new ProductKitItemStub(11);
        $kitItemLineItem1 = (new ProductKitItemLineItemStub(10))
            ->setKitItem($kitItem1)
            ->setProduct($kitItemLineItem1Product)
            ->setUnit($productUnitItem)
            ->setQuantity(11);
        $kitItemLineItem2Product = (new ProductStub())->setId(2);
        $kitItem2 = new ProductKitItemStub(22);
        $kitItemLineItem2 = (new ProductKitItemLineItemStub(20))
            ->setKitItem($kitItem2)
            ->setProduct($kitItemLineItem2Product)
            ->setUnit($productUnitEach)
            ->setQuantity(22);
        $productKit = (new ProductStub())->setId(100)->setType(Product::TYPE_KIT);
        $kitLineItem = (new ProductKitItemLineItemsAwareStub(42))
            ->setProduct($productKit)
            ->setUnit($productUnitEach)
            ->setQuantity(111)
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);

        $kitItem1Price = new ProductKitItemPriceDTO(
            $kitItem1,
            $kitItemLineItem1Product,
            Price::create(1.234, self::USD),
            1,
            $productUnitItem
        );
        $productKitPrice = (new ProductKitPriceDTO($productKit, Price::create(12.345, self::USD), 1, $productUnitItem))
            ->addKitItemPrice($kitItem1Price);

        $kitItemLineItem1Price = new ProductKitItemLineItemPrice(
            $kitItemLineItem1,
            $kitItem1Price->getPrice(),
            $kitItem1Price->getPrice()->getValue() * $kitItemLineItem1->getQuantity()
        );
        $this->productLineItemPriceFactory
            ->expects(self::once())
            ->method('createForProductLineItem')
            ->with($kitItemLineItem1, $kitItem1Price)
            ->willReturn($kitItemLineItem1Price);

        self::assertNull(
            $this->factory->createForProductLineItem($kitLineItem, $productKitPrice)
        );
    }

    public function testCreateForProductLineItemWhenNoOptionalKitItemPrice(): void
    {
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productUnitEach = (new ProductUnit())->setCode('each');
        $kitItemLineItem1Product = (new ProductStub())->setId(1);
        $kitItem1 = new ProductKitItemStub(11);
        $kitItemLineItem1 = (new ProductKitItemLineItemStub(10))
            ->setKitItem($kitItem1)
            ->setProduct($kitItemLineItem1Product)
            ->setUnit($productUnitItem)
            ->setQuantity(11);
        $kitItemLineItem2Product = (new ProductStub())->setId(2);
        $kitItem2 = (new ProductKitItemStub(22))
            ->setOptional(true);
        $kitItemLineItem2 = (new ProductKitItemLineItemStub(20))
            ->setKitItem($kitItem2)
            ->setProduct($kitItemLineItem2Product)
            ->setUnit($productUnitEach)
            ->setQuantity(22);
        $productKit = (new ProductStub())->setId(100)->setType(Product::TYPE_KIT);
        $kitLineItem = (new ProductKitItemLineItemsAwareStub(42))
            ->setProduct($productKit)
            ->setUnit($productUnitEach)
            ->setQuantity(111)
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);

        $kitItem1Price = new ProductKitItemPriceDTO(
            $kitItem1,
            $kitItemLineItem1Product,
            Price::create(1.234, self::USD),
            1,
            $productUnitItem
        );

        $kitItemLineItem1Price = new ProductKitItemLineItemPrice(
            $kitItemLineItem1,
            $kitItem1Price->getPrice(),
            $kitItem1Price->getPrice()->getValue() * $kitItemLineItem1->getQuantity()
        );

        $productKitPrice = (new ProductKitPriceDTO(
            $productKit,
            Price::create(12.345 + $kitItemLineItem1Price->getSubtotal(), self::USD),
            1,
            $productUnitItem
        ))
            ->addKitItemPrice($kitItem1Price);

        $this->productLineItemPriceFactory
            ->expects(self::once())
            ->method('createForProductLineItem')
            ->with($kitItemLineItem1, $kitItem1Price)
            ->willReturn($kitItemLineItem1Price);

        $kitLineItemPrice = (new ProductKitLineItemPrice($kitLineItem, Price::create(25.919, self::USD), 2877.01))
            ->addKitItemLineItemPrice($kitItemLineItem1Price);

        self::assertEquals(
            $kitLineItemPrice,
            $this->factory->createForProductLineItem($kitLineItem, $productKitPrice)
        );
    }
}
