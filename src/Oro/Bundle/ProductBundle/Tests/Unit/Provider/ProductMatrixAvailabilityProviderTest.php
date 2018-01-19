<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ProductBundle\Provider\ProductMatrixAvailabilityProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductMatrixAvailabilityProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ProductVariantAvailabilityProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $productVariantAvailability;

    /** @var ProductMatrixAvailabilityProvider */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->productVariantAvailability = $this->createMock(ProductVariantAvailabilityProvider::class);

        $this->provider = new ProductMatrixAvailabilityProvider(
            $this->productVariantAvailability
        );
    }

    public function testIsMatrixFormAvailableWithSimpleProduct()
    {
        /** @var Product $simpleProduct */
        $simpleProduct = $this->getEntity(Product::class);
        $this->productVariantAvailability->expects($this->never())
            ->method('getVariantFieldsAvailability');

        $this->assertEquals(false, $this->provider->isMatrixFormAvailable($simpleProduct));
    }

    /**
     * @param array $attributes
     * @param ProductUnitPrecision $unitPrecision
     * @param Product[] $simpleProducts
     * @param bool $expected
     *
     * @dataProvider isMatrixFormAvailableProvider
     */
    public function testIsMatrixFormAvailableWithOneArgument(
        array $attributes,
        ProductUnitPrecision $unitPrecision,
        array $simpleProducts,
        bool $expected
    ) {
        /** @var Product $product */
        $product = $this->getEntity(Product::class, [
            'id' => 123,
            'primaryUnitPrecision' => $unitPrecision,
            'type' => Product::TYPE_CONFIGURABLE
        ]);

        $this->productVariantAvailability->expects($this->once())
            ->method('getVariantFieldsAvailability')
            ->with($product)
            ->willReturn($attributes);

        // Matrix form should be available only for 1 or 2 attributes
        $this->productVariantAvailability->expects($this->exactly(count($attributes) <= 2 ? 1 : 0))
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
        $unit = $this->getEntity(ProductUnit::class);
        $unitPrecision = $this->getEntity(ProductUnitPrecision::class, ['unit' => $unit]);

        $simpleProduct = $this->getEntity(Product::class, ['id' => 321, 'primaryUnitPrecision' => $unitPrecision]);

        return [
            'one attribute' => [
                'attributes' => [1],
                'unitPrecision' => $unitPrecision,
                'simpleProducts' => [$simpleProduct],
                'expected' => true,
            ],
            'two attributes' => [
                'attributes' => [1, 2],
                'unitPrecision' => $unitPrecision,
                'simpleProducts' => [$simpleProduct],
                'expected' => true,
            ],
            'two attributes, no simple' => [
                'attributes' => [1, 2],
                'unitPrecision' => $unitPrecision,
                'simpleProducts' => [],
                'expected' => false,
            ],
            'tree attributes' => [
                'attributes' => [1, 2, 3],
                'unitPrecision' => $unitPrecision,
                'simpleProducts' => [$simpleProduct],
                'expected' => false,
            ],
        ];
    }
}
