<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\ProductMatrixAvailabilityProvider;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;

class ProductMatrixAvailabilityProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductVariantAvailabilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productVariantAvailability;

    /** @var ProductMatrixAvailabilityProvider */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->productVariantAvailability = $this->createMock(ProductVariantAvailabilityProvider::class);

        $this->provider = new ProductMatrixAvailabilityProvider($this->productVariantAvailability);
    }

    public function testIsMatrixFormAvailableWithSimpleProduct()
    {
        /** @var Product $simpleProduct */
        $simpleProduct = new ProductStub();
        $this->productVariantAvailability->expects($this->never())
            ->method('getVariantFieldsAvailability');

        $this->assertEquals(false, $this->provider->isMatrixFormAvailable($simpleProduct));
    }

    /**
     * @param array $variantFields
     * @param ProductUnitPrecision $unitPrecision
     * @param Product[] $simpleProducts
     * @param bool $expected
     *
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

        // Matrix form should be available only for 1 or 2 attributes
        $this->productVariantAvailability->expects($this->exactly(count($variantFields) <= 2 ? 1 : 0))
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn($simpleProducts);

        $this->assertEquals($expected, $this->provider->isMatrixFormAvailable($product));
        // test cache
        $this->assertEquals($expected, $this->provider->isMatrixFormAvailable($product));
    }

    /**
     * @return array
     */
    public function isMatrixFormAvailableProvider()
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

        $this->productVariantAvailability
            ->expects($this->never())
            ->method('getSimpleProductsGroupedByConfigurable');

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
}
