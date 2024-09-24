<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\ProductKit\ProductPriceCriteria\DataExtractor;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaDataExtractor\ProductPriceCriteriaDataExtractorInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\DataExtractor\ProductKitPriceCriteriaDataExtractor;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\ProductKitItemPriceCriteria;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\ProductKitPriceCriteria;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductKitPriceCriteriaDataExtractorTest extends TestCase
{
    private ProductPriceCriteriaDataExtractorInterface|MockObject $productPriceCriteriaDataExtractor;

    private ProductKitPriceCriteriaDataExtractor $extractor;

    #[\Override]
    protected function setUp(): void
    {
        $this->productPriceCriteriaDataExtractor = $this->createMock(ProductPriceCriteriaDataExtractorInterface::class);

        $this->extractor = new ProductKitPriceCriteriaDataExtractor($this->productPriceCriteriaDataExtractor);
    }

    public function testIsSupportedWhenNotSupported(): void
    {
        self::assertFalse($this->extractor->isSupported($this->createMock(ProductPriceCriteria::class)));
    }

    public function testIsSupportedWhenSupported(): void
    {
        self::assertTrue($this->extractor->isSupported($this->createMock(ProductKitPriceCriteria::class)));
    }

    public function testExtractCriteriaDataWhenNotSupported(): void
    {
        self::assertEquals(
            [
                ProductPriceCriteriaDataExtractorInterface::PRODUCT_IDS => [],
                ProductPriceCriteriaDataExtractorInterface::UNIT_CODES => [],
                ProductPriceCriteriaDataExtractorInterface::CURRENCIES => [],
            ],
            $this->extractor->extractCriteriaData($this->createMock(ProductPriceCriteria::class))
        );
    }

    public function testExtractCriteriaDataWhenSupported(): void
    {
        $productKit = (new ProductStub())->setId(42);
        $kitItem1 = new ProductKitItemStub(10);
        $kitItem1Product = (new ProductStub())->setId(420);
        $kitItem2 = new ProductKitItemStub(20);
        $kitItem2Product = (new ProductStub())->setId(4200);
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productUnitEach = (new ProductUnit())->setCode('each');
        $currency = 'USD';

        $productKitPriceCriteria = new ProductKitPriceCriteria(
            $productKit,
            $productUnitItem,
            12.345,
            $currency
        );

        $kitItem1PriceCriteria = new ProductKitItemPriceCriteria(
            $kitItem1,
            $kitItem1Product,
            $productUnitItem,
            34.567,
            $currency
        );

        $kitItem2PriceCriteria = new ProductKitItemPriceCriteria(
            $kitItem2,
            $kitItem2Product,
            $productUnitEach,
            56.789,
            $currency
        );

        $productKitPriceCriteria
            ->addKitItemProductPriceCriteria($kitItem1PriceCriteria)
            ->addKitItemProductPriceCriteria($kitItem2PriceCriteria);

        $this->productPriceCriteriaDataExtractor
            ->expects(self::exactly(2))
            ->method('extractCriteriaData')
            ->willReturnMap([
                [
                    $kitItem1PriceCriteria,
                    [
                        ProductPriceCriteriaDataExtractorInterface::PRODUCT_IDS => [$kitItem1Product->getId()],
                        ProductPriceCriteriaDataExtractorInterface::UNIT_CODES => [$productUnitItem->getCode()],
                        ProductPriceCriteriaDataExtractorInterface::CURRENCIES => [$currency],
                    ],
                ],
                [
                    $kitItem2PriceCriteria,
                    [
                        ProductPriceCriteriaDataExtractorInterface::PRODUCT_IDS => [$kitItem2Product->getId()],
                        ProductPriceCriteriaDataExtractorInterface::UNIT_CODES => [$productUnitEach->getCode()],
                        ProductPriceCriteriaDataExtractorInterface::CURRENCIES => [$currency],
                    ],
                ],
            ]);

        self::assertEquals(
            [
                ProductPriceCriteriaDataExtractorInterface::PRODUCT_IDS => [
                    $productKit->getId(),
                    $kitItem1Product->getId(),
                    $kitItem2Product->getId(),
                ],
                ProductPriceCriteriaDataExtractorInterface::UNIT_CODES => [
                    $productUnitItem->getCode(),
                    $productUnitEach->getCode(),
                ],
                ProductPriceCriteriaDataExtractorInterface::CURRENCIES => [$currency],
            ],
            $this->extractor->extractCriteriaData($productKitPriceCriteria)
        );
    }

    public function testExtractCriteriaDataWhenNotKitItems(): void
    {
        $productKit = (new ProductStub())->setId(42);
        $productUnitItem = (new ProductUnit())->setCode('item');
        $currency = 'USD';

        $productKitPriceCriteria = new ProductKitPriceCriteria(
            $productKit,
            $productUnitItem,
            12.345,
            $currency
        );

        $this->productPriceCriteriaDataExtractor
            ->expects(self::never())
            ->method('extractCriteriaData');

        self::assertEquals(
            [
                ProductPriceCriteriaDataExtractorInterface::PRODUCT_IDS => [$productKit->getId()],
                ProductPriceCriteriaDataExtractorInterface::UNIT_CODES => [$productUnitItem->getCode()],
                ProductPriceCriteriaDataExtractorInterface::CURRENCIES => [$currency],
            ],
            $this->extractor->extractCriteriaData($productKitPriceCriteria)
        );
    }
}
