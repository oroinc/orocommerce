<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ProductVariant\Form\Type\DataTransformer;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ProductVariant\Form\Type\DataTransformer\ProductVariantFieldsToProductVariantTransformer;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;

class ProductVariantFieldsToProductVariantTransformerTest extends \PHPUnit\Framework\TestCase
{
    private const PRODUCT_CLASS = Product::class;

    /** @var Product */
    private $parentProduct;

    /** @var ProductVariantFieldsToProductVariantTransformer */
    private $dataTransformer;

    /** @var ProductVariantAvailabilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productVariantAvailabilityProvider;

    protected function setUp(): void
    {
        $this->parentProduct = new Product();
        $this->productVariantAvailabilityProvider = $this->createMock(ProductVariantAvailabilityProvider::class);

        $this->dataTransformer = new ProductVariantFieldsToProductVariantTransformer(
            $this->parentProduct,
            $this->productVariantAvailabilityProvider,
            self::PRODUCT_CLASS
        );
    }

    public function testTransform()
    {
        $value = new Product();
        $actual = $this->dataTransformer->transform($value);
        $this->assertSame($value, $actual);
    }

    public function testTransformWithIncorrectValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Value to transform of type ' . self::PRODUCT_CLASS . ' expected, but ' . \stdClass::class . ' given'
        );

        $value = new \stdClass();
        $actual = $this->dataTransformer->transform($value);
        $this->assertSame($value, $actual);
    }

    public function testReverseTransform()
    {
        $value = new Product();

        $fields = [
            'color' => 'red',
            'new' => true,
        ];

        $variant = new Product();

        $this->productVariantAvailabilityProvider->expects($this->once())
            ->method('getVariantFieldsValuesForVariant')
            ->with($this->parentProduct, $value)
            ->willReturn($fields);

        $this->productVariantAvailabilityProvider->expects($this->once())
            ->method('getSimpleProductByVariantFields')
            ->with($this->parentProduct, $fields, false)
            ->willReturn($variant);

        $actual = $this->dataTransformer->reverseTransform($value);
        $this->assertSame($variant, $actual);
    }

    public function testReverseTransformWithIncorrectValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Value to reverse transform of type ' . self::PRODUCT_CLASS .
            ' expected, but ' . \stdClass::class . ' given'
        );

        $value = new \stdClass();
        $actual = $this->dataTransformer->reverseTransform($value);
        $this->assertSame($value, $actual);
    }
}
