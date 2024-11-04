<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\ProductKit\PriceByMatchingCriteria;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\ProductKit\PriceByMatchingCriteria\ProductKitPriceByMatchingCriteriaProvider;
use Oro\Bundle\PricingBundle\ProductKit\ProductPrice\ProductKitItemPriceDTO;
use Oro\Bundle\PricingBundle\ProductKit\ProductPrice\ProductKitPriceDTO;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\ProductKitItemPriceCriteria;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\ProductKitPriceCriteria;
use Oro\Bundle\PricingBundle\Provider\PriceByMatchingCriteria\ProductPriceByMatchingCriteriaProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductKitPriceByMatchingCriteriaProviderTest extends TestCase
{
    private const USD = 'USD';

    private ProductPriceByMatchingCriteriaProviderInterface|MockObject $simpleProductPriceByMatchingCriteriaProvider;

    private ProductKitPriceByMatchingCriteriaProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->simpleProductPriceByMatchingCriteriaProvider = $this->createMock(
            ProductPriceByMatchingCriteriaProviderInterface::class
        );
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService
            ->method('round')
            ->willReturnCallback(static fn ($value) => round($value, 2));

        $this->provider = new ProductKitPriceByMatchingCriteriaProvider(
            $this->simpleProductPriceByMatchingCriteriaProvider,
            $roundingService
        );
    }

    /**
     * @dataProvider isSupportedDataProvider
     */
    public function testIsSupported(ProductPriceCriteria $productPriceCriteria, bool $expected): void
    {
        self::assertEquals(
            $expected,
            $this->provider->isSupported($productPriceCriteria, new ProductPriceCollectionDTO())
        );
    }

    public function isSupportedDataProvider(): array
    {
        return [
            ['productPriceCriteria' => $this->createMock(ProductPriceCriteria::class), 'expected' => false],
            ['productPriceCriteria' => $this->createMock(ProductKitPriceCriteria::class), 'expected' => true],
        ];
    }

    public function testGetProductPriceMatchingCriteriaWhenNotSupported(): void
    {
        self::assertNull(
            $this->provider->getProductPriceMatchingCriteria(
                $this->createMock(ProductPriceCriteria::class),
                new ProductPriceCollectionDTO()
            )
        );
    }

    public function testGetProductPriceMatchingCriteriaWhenNoMatchingPriceForProductKitAndNoKitItems(): void
    {
        $productKit = (new ProductStub())
            ->setId(42)
            ->setType(Product::TYPE_KIT);
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productPriceCriteria = new ProductKitPriceCriteria($productKit, $productUnitItem, 1.2345, self::USD);
        $productPriceCollection = new ProductPriceCollectionDTO();
        $this->simpleProductPriceByMatchingCriteriaProvider
            ->expects(self::once())
            ->method('getProductPriceMatchingCriteria')
            ->with($productPriceCriteria, $productPriceCollection)
            ->willReturn(null);

        self::assertEquals(
            new ProductKitPriceDTO(
                $productPriceCriteria->getProduct(),
                Price::create(0.0, $productPriceCriteria->getCurrency()),
                1.0,
                $productPriceCriteria->getProductUnit()
            ),
            $this->provider->getProductPriceMatchingCriteria($productPriceCriteria, $productPriceCollection)
        );
    }

    public function testGetProductPriceMatchingCriteriaWhenNoMatchingPriceForProductKitAndForOptionalKitItem(): void
    {
        $kitItem1 = (new ProductKitItemStub(1))
            ->setOptional(true);
        $kitItem1Product = (new ProductStub())
            ->setId(10);
        $kitItem2 = new ProductKitItemStub(2);
        $kitItem2Product = (new ProductStub())
            ->setId(20);
        $productKit = (new ProductStub())
            ->setId(42)
            ->setType(Product::TYPE_KIT);

        $productUnitItem = (new ProductUnit())->setCode('item');
        $kitItem1PriceCriteria = new ProductKitItemPriceCriteria(
            $kitItem1,
            $kitItem1Product,
            $productUnitItem,
            0.1234,
            self::USD
        );
        $kitItem2PriceCriteria = new ProductKitItemPriceCriteria(
            $kitItem2,
            $kitItem2Product,
            $productUnitItem,
            0.5678,
            self::USD
        );
        $productKitPriceCriteria = (new ProductKitPriceCriteria($productKit, $productUnitItem, 1.2345, self::USD))
            ->addKitItemProductPriceCriteria($kitItem1PriceCriteria)
            ->addKitItemProductPriceCriteria($kitItem2PriceCriteria);
        $productPriceCollection = new ProductPriceCollectionDTO();

        $kitItem2ProductPriceDTO = new ProductPriceDTO(
            $kitItem2Product,
            Price::create(22.3344, self::USD),
            1,
            $productUnitItem
        );
        $this->simpleProductPriceByMatchingCriteriaProvider
            ->expects(self::exactly(3))
            ->method('getProductPriceMatchingCriteria')
            ->willReturnMap([
                [$productKitPriceCriteria, $productPriceCollection, null],
                [$kitItem1PriceCriteria, $productPriceCollection, null],
                [
                    $kitItem2PriceCriteria,
                    $productPriceCollection,
                    $kitItem2ProductPriceDTO,
                ],
            ]);

        $kitItem2PriceDTO = new ProductKitItemPriceDTO(
            $kitItem2,
            $kitItem2Product,
            Price::create(22.3344, self::USD),
            1,
            $productUnitItem
        );
        $productKitPriceDTO = (new ProductKitPriceDTO(
            $productKitPriceCriteria->getProduct(),
            Price::create(12.68, $productKitPriceCriteria->getCurrency()),
            1.0,
            $productKitPriceCriteria->getProductUnit()
        ))
            ->addKitItemPrice($kitItem2PriceDTO);

        self::assertEquals(
            $productKitPriceDTO,
            $this->provider->getProductPriceMatchingCriteria($productKitPriceCriteria, $productPriceCollection)
        );
    }

    public function testGetProductPriceMatchingCriteriaWhenNoMatchingPriceForProductKitAndForRequiredKitItem(): void
    {
        $kitItem1 = (new ProductKitItemStub(1));
        $kitItem1Product = (new ProductStub())
            ->setId(10);
        $kitItem2 = new ProductKitItemStub(2);
        $kitItem2Product = (new ProductStub())
            ->setId(20);
        $productKit = (new ProductStub())
            ->setId(42)
            ->setType(Product::TYPE_KIT);

        $productUnitItem = (new ProductUnit())->setCode('item');
        $kitItem1PriceCriteria = new ProductKitItemPriceCriteria(
            $kitItem1,
            $kitItem1Product,
            $productUnitItem,
            0.1234,
            self::USD
        );
        $kitItem2PriceCriteria = new ProductKitItemPriceCriteria(
            $kitItem2,
            $kitItem2Product,
            $productUnitItem,
            0.5678,
            self::USD
        );
        $productKitPriceCriteria = (new ProductKitPriceCriteria($productKit, $productUnitItem, 1.2345, self::USD))
            ->addKitItemProductPriceCriteria($kitItem1PriceCriteria)
            ->addKitItemProductPriceCriteria($kitItem2PriceCriteria);
        $productPriceCollection = new ProductPriceCollectionDTO();

        $this->simpleProductPriceByMatchingCriteriaProvider
            ->expects(self::exactly(2))
            ->method('getProductPriceMatchingCriteria')
            ->willReturnMap([
                [$productKitPriceCriteria, $productPriceCollection, null],
                [$kitItem1PriceCriteria, $productPriceCollection, null],
            ]);

        self::assertNull(
            $this->provider->getProductPriceMatchingCriteria($productKitPriceCriteria, $productPriceCollection)
        );
    }

    public function testGetProductPriceMatchingCriteriaWhenHasMatchingPriceForProductKitAndHasKitItems(): void
    {
        $kitItem1 = (new ProductKitItemStub(1))
            ->setOptional(true);
        $kitItem1Product = (new ProductStub())
            ->setId(10);
        $kitItem2 = new ProductKitItemStub(2);
        $kitItem2Product = (new ProductStub())
            ->setId(20);
        $productKit = (new ProductStub())
            ->setId(42)
            ->setType(Product::TYPE_KIT);

        $productUnitItem = (new ProductUnit())->setCode('item');
        $kitItem1PriceCriteria = new ProductKitItemPriceCriteria(
            $kitItem1,
            $kitItem1Product,
            $productUnitItem,
            0.1234,
            self::USD
        );
        $kitItem2PriceCriteria = new ProductKitItemPriceCriteria(
            $kitItem2,
            $kitItem2Product,
            $productUnitItem,
            0.5678,
            self::USD
        );
        $productKitPriceCriteria = (new ProductKitPriceCriteria($productKit, $productUnitItem, 1.2345, self::USD))
            ->addKitItemProductPriceCriteria($kitItem1PriceCriteria)
            ->addKitItemProductPriceCriteria($kitItem2PriceCriteria);
        $productPriceCollection = new ProductPriceCollectionDTO();

        $this->simpleProductPriceByMatchingCriteriaProvider
            ->expects(self::exactly(3))
            ->method('getProductPriceMatchingCriteria')
            ->willReturnMap([
                [
                    $productKitPriceCriteria,
                    $productPriceCollection,
                    new ProductPriceDTO(
                        $productKit,
                        Price::create(0.1122, self::USD),
                        1,
                        $productUnitItem
                    ),
                ],
                [$kitItem1PriceCriteria, $productPriceCollection, null],
                [
                    $kitItem2PriceCriteria,
                    $productPriceCollection,
                    new ProductPriceDTO(
                        $kitItem2Product,
                        Price::create(22.3344, self::USD),
                        1,
                        $productUnitItem
                    ),
                ],
            ]);

        $kitItem2PriceDTO = new ProductKitItemPriceDTO(
            $kitItem2,
            $kitItem2Product,
            Price::create(22.3344, self::USD),
            1,
            $productUnitItem
        );
        $productKitPriceDTO = (new ProductKitPriceDTO(
            $productKit,
            Price::create(12.7922, self::USD),
            1.0,
            $productUnitItem
        ))
            ->addKitItemPrice($kitItem2PriceDTO);

        self::assertEquals(
            $productKitPriceDTO,
            $this->provider->getProductPriceMatchingCriteria($productKitPriceCriteria, $productPriceCollection)
        );
    }
}
