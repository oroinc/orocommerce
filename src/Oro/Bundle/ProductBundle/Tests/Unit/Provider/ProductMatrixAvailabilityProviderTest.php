<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ProductBundle\Provider\ProductMatrixAvailabilityProvider;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
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
     * @dataProvider isMatrixFormAvailableProvider
     */
    public function testIsMatrixFormAvailableWithOneArgument($attributes, $expected)
    {
        $unit = $this->getEntity(ProductUnit::class);
        $unitPrecision = $this->getEntity(ProductUnitPrecision::class, ['unit' => $unit]);

        /** @var Product $product */
        $product = $this->getEntity(Product::class, [
            'id' => 123,
            'primaryUnitPrecision' => $unitPrecision,
            'type' => Product::TYPE_CONFIGURABLE
        ]);

        $simpleProduct = $this->getEntity(Product::class, ['id' => 321, 'primaryUnitPrecision' => $unitPrecision]);

        $this->productVariantAvailability->expects($this->once())
            ->method('getVariantFieldsAvailability')
            ->with($product)
            ->willReturn($attributes);

        $this->productVariantAvailability->expects($this->exactly($expected ? 1 : 0))
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn([$simpleProduct]);

        $this->assertEquals($expected, $this->provider->isMatrixFormAvailable($product));
    }

    public function isMatrixFormAvailableProvider()
    {
        return [
            'one attribute' => [[1], true],
            'two attribute' => [[1, 2], true],
            'tree attribute' => [[1, 2, 3], false],
        ];
    }
}
