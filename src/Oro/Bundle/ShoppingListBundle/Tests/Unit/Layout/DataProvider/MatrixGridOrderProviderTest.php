<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\MatrixGridOrderProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class MatrixGridOrderProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ProductVariantAvailabilityProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productVariantAvailability;

    /** @var MatrixGridOrderProvider */
    private $provider;

    protected function setUp()
    {
        $this->productVariantAvailability = $this->createMock(ProductVariantAvailabilityProvider::class);
        $this->provider = new MatrixGridOrderProvider($this->productVariantAvailability);
    }

    public function testIsAvailable()
    {
        $unit = $this->getEntity(ProductUnit::class);
        $unitPrecision = $this->getEntity(ProductUnitPrecision::class, ['unit' => $unit]);

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['primaryUnitPrecision' => $unitPrecision]);
        $simpleProduct = $this->getEntity(Product::class, ['primaryUnitPrecision' => $unitPrecision]);

        $this->productVariantAvailability->expects($this->once())
            ->method('getVariantFieldsWithAvailability')
            ->with($product)
            ->willReturn([[1]]);

        $this->productVariantAvailability->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn([$simpleProduct]);

        $this->assertEquals(true, $this->provider->isAvailable($product));
    }

    public function testIsAvailableReturnsFalseOnSimpleProduct()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class);

        $this->productVariantAvailability->expects($this->once())
            ->method('getVariantFieldsWithAvailability')
            ->with($product)
            ->willThrowException(new \InvalidArgumentException());

        $this->assertEquals(false, $this->provider->isAvailable($product));
    }

    public function testIsAvailableReturnsFalseOnMoreThanTwoVariantFields()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class);

        $this->productVariantAvailability->expects($this->once())
            ->method('getVariantFieldsWithAvailability')
            ->with($product)
            ->willReturn([[], [], []]);

        $this->assertEquals(false, $this->provider->isAvailable($product));
    }

    public function testIsAvailableReturnsFalseOnMoreThanFiveFirstFieldVariants()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class);

        $this->productVariantAvailability->expects($this->once())
            ->method('getVariantFieldsWithAvailability')
            ->with($product)
            ->willReturn([[1, 2, 3, 4, 5, 6, 7]]);

        $this->assertEquals(false, $this->provider->isAvailable($product));
    }

    public function testIsAvailableReturnsFalseOnUnitNotSupportedBySimpleProduct()
    {
        $unit = $this->getEntity(ProductUnit::class);
        $unitPrecision = $this->getEntity(ProductUnitPrecision::class, ['unit' => $unit]);

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['primaryUnitPrecision' => $unitPrecision]);

        $simpleProductUnit = $this->getEntity(ProductUnit::class);
        $simpleProductUnitPrecision = $this->getEntity(ProductUnitPrecision::class, ['unit' => $simpleProductUnit]);

        /** @var Product $product */
        $simpleProduct = $this->getEntity(Product::class, ['primaryUnitPrecision' => $simpleProductUnitPrecision]);

        $this->productVariantAvailability->expects($this->once())
            ->method('getVariantFieldsWithAvailability')
            ->with($product)
            ->willReturn([[1]]);

        $this->productVariantAvailability->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn([$simpleProduct]);

        $this->assertEquals(false, $this->provider->isAvailable($product));
    }
}
