<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\FrontendProductUnitsProvider;
use Oro\Bundle\ProductBundle\Provider\ProductMatrixAvailabilityProvider;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;

class ProductMatrixAvailabilityProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductVariantAvailabilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $variantAvailability;

    /** @var FrontendProductUnitsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productUnitsProvider;

    /** @var ProductMatrixAvailabilityProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->variantAvailability = $this->createMock(ProductVariantAvailabilityProvider::class);
        $this->productUnitsProvider = $this->createMock(FrontendProductUnitsProvider::class);

        $this->provider = new ProductMatrixAvailabilityProvider(
            $this->variantAvailability,
            $this->productUnitsProvider
        );
    }

    public function testIsMatrixFormAvailableWithSimpleProduct()
    {
        $simpleProduct = new ProductStub();
        $this->variantAvailability->expects($this->never())
            ->method('getVariantFieldsAvailability');

        $this->assertEquals(false, $this->provider->isMatrixFormAvailable($simpleProduct));
    }

    /**
     * @dataProvider isMatrixFormAvailableProvider
     */
    public function testIsMatrixFormAvailableWithOneArgument(
        array $variantFields,
        ProductUnitPrecision $unitPrecision,
        array $simpleProducts,
        bool $expected
    ) {
        $product = (new ProductStub())
            ->setId(123)
            ->setPrimaryUnitPrecision($unitPrecision)
            ->setType(Product::TYPE_CONFIGURABLE)
            ->setVariantFields($variantFields);

        // matrix form should be available only for 1 or 2 attributes
        $this->variantAvailability->expects($this->exactly(count($variantFields) <= 2 ? 1 : 0))
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn($simpleProducts);

        $this->assertEquals($expected, $this->provider->isMatrixFormAvailable($product));
        // test cache
        $this->assertEquals($expected, $this->provider->isMatrixFormAvailable($product));
    }

    public function isMatrixFormAvailableProvider(): array
    {
        $unit = new ProductUnit();
        $unitPrecision = (new ProductUnitPrecision())->setUnit($unit);
        $simpleProduct = (new ProductStub())
            ->setId(321)
            ->setPrimaryUnitPrecision($unitPrecision);

        return [
            'one attribute' => [
                'variantFields' => ['sampleField1'],
                'unitPrecision' => $unitPrecision,
                'simpleProducts' => [$simpleProduct],
                'expected' => true,
            ],
            'two attributes' => [
                'variantFields' => ['sampleField1', 'sampleField2'],
                'unitPrecision' => $unitPrecision,
                'simpleProducts' => [$simpleProduct],
                'expected' => true,
            ],
            'two attributes, no simple' => [
                'variantFields' => ['sampleField1', 'sampleField2'],
                'unitPrecision' => $unitPrecision,
                'simpleProducts' => [],
                'expected' => false,
            ],
            'tree attributes' => [
                'variantFields' => ['sampleField1', 'sampleField2', 'sampleField3'],
                'unitPrecision' => $unitPrecision,
                'simpleProducts' => [$simpleProduct],
                'expected' => false,
            ],
        ];
    }

    public function testIsMatrixFormAvailableForProductsWhenNotConfigurable(): void
    {
        $simpleProduct = new ProductStub();

        $this->variantAvailability->expects($this->never())
            ->method('getSimpleProductIdsGroupedByConfigurable');

        $this->assertEmpty($this->provider->isMatrixFormAvailableForProducts([$simpleProduct]));
    }

    /**
     * @dataProvider isMatrixFormAvailableForProductsDataProvider
     */
    public function testIsMatrixFormAvailableForProducts(Product $configurableProduct, array $expectedResult): void
    {
        $this->assertSame($expectedResult, $this->provider->isMatrixFormAvailableForProducts([$configurableProduct]));
    }

    public function isMatrixFormAvailableForProductsDataProvider(): array
    {
        $productWith0VariantFields = (new ProductStub())
            ->setId(123)
            ->setType(Product::TYPE_CONFIGURABLE);

        $productWith2VariantFields = (new ProductStub())
            ->setId(456)
            ->setType(Product::TYPE_CONFIGURABLE)
            ->setVariantFields(['sampleField1', 'sampleField2']);

        $productWith3VariantFields = (new ProductStub())
            ->setId(789)
            ->setType(Product::TYPE_CONFIGURABLE)
            ->setVariantFields(['sampleField1', 'sampleField2', 'sampleField3']);

        return [
            '0 attributes' => [
                'configurableProduct' => $productWith0VariantFields,
                'expectedResult' => [],
            ],
            '2 attributes' => [
                'configurableProduct' => $productWith2VariantFields,
                'expectedResult' => [$productWith2VariantFields->getId() => $productWith2VariantFields],
            ],
            '3 attributes' => [
                'configurableProduct' => $productWith3VariantFields,
                'expectedResult' => [],
            ],
        ];
    }

    public function testGetMatrixAvailabilityByConfigurableProductDataWhenVariantsCountIsNotAcceptable(): void
    {
        $configurableProductData = [
            100 => ['each', 0]
        ];
        $matrixAvailability = [
            100 => false
        ];

        $this->variantAvailability->expects($this->never())
            ->method('getSimpleProductIdsByVariantFieldsGroupedByConfigurable');

        $this->assertSame(
            $matrixAvailability,
            $this->provider->getMatrixAvailabilityByConfigurableProductData($configurableProductData)
        );
    }

    public function testGetMatrixAvailabilityByConfigurableProductData(): void
    {
        $configurableProductData = [
            100 => ['each', 1],
            101 => ['each', 2],
            102 => ['each', 0],
            103 => ['each', 2],
            104 => ['kg', 2]
        ];
        $matrixAvailability = [
            102 => false, // variants count is not acceptable
            100 => true,
            101 => false, // no product units for simple product
            103 => false, // no simple products
            104 => false // not all simple products have applicable product units
        ];
        $configurableProductIdsForProductsWithAcceptableVariantsCount = [100, 101, 103, 104];
        $simpleProducts = [
            100 => [11, 12],
            101 => [13],
            104 => [14, 15],
        ];
        $simpleProductIds = [11, 12, 13, 14, 15];
        $simpleProductUnits = [
            11 => ['items', 'each'],
            12 => ['each'],
            14 => ['items', 'kg'],
            15 => ['each']
        ];

        $this->variantAvailability->expects($this->once())
            ->method('getSimpleProductIdsByVariantFieldsGroupedByConfigurable')
            ->with($configurableProductIdsForProductsWithAcceptableVariantsCount)
            ->willReturn($simpleProducts);

        $this->productUnitsProvider->expects($this->once())
            ->method('getUnitsForProducts')
            ->with($simpleProductIds)
            ->willReturn($simpleProductUnits);

        $this->assertSame(
            $matrixAvailability,
            $this->provider->getMatrixAvailabilityByConfigurableProductData($configurableProductData)
        );
    }
}
