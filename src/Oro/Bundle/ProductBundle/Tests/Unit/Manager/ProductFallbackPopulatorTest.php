<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Manager;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Manager\ProductFallbackPopulator;
use Oro\Bundle\ProductBundle\Provider\ProductFallbackFieldProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final class ProductFallbackPopulatorTest extends TestCase
{
    private PropertyAccessorInterface&MockObject $propertyAccessor;
    private ProductFallbackFieldProviderInterface&MockObject $fieldProvider;
    private ProductFallbackPopulator $populator;

    #[\Override]
    protected function setUp(): void
    {
        $this->propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $this->fieldProvider = $this->createMock(ProductFallbackFieldProviderInterface::class);

        $this->populator = new ProductFallbackPopulator(
            $this->propertyAccessor,
            $this->fieldProvider
        );
    }

    public function testPopulateWhenNoFieldsAreConfigured(): void
    {
        $product = new Product();

        $this->fieldProvider->expects(self::once())
            ->method('getFieldsByFallbackId')
            ->willReturn([]);

        $this->propertyAccessor->expects(self::never())
            ->method('getValue');

        $result = $this->populator->populate($product);

        self::assertFalse($result);
    }

    public function testPopulateWhenFieldAlreadyHasValue(): void
    {
        $product = new Product();
        $existingFallback = new EntityFieldFallbackValue();

        $this->fieldProvider->expects(self::once())
            ->method('getFieldsByFallbackId')
            ->willReturn([
                'category' => ['pageTemplate']
            ]);

        $this->propertyAccessor->expects(self::once())
            ->method('getValue')
            ->with($product, 'pageTemplate')
            ->willReturn($existingFallback);

        $this->propertyAccessor->expects(self::never())
            ->method('setValue');

        $result = $this->populator->populate($product);

        self::assertFalse($result);
    }

    public function testPopulateWhenFieldIsNullCreatesNewFallback(): void
    {
        $product = new Product();

        $this->fieldProvider->expects(self::once())
            ->method('getFieldsByFallbackId')
            ->willReturn([
                'category' => ['pageTemplate']
            ]);

        $this->propertyAccessor->expects(self::once())
            ->method('getValue')
            ->with($product, 'pageTemplate')
            ->willReturn(null);

        $this->propertyAccessor->expects(self::once())
            ->method('setValue')
            ->with(
                $product,
                'pageTemplate',
                self::callback(function ($value) {
                    return $value instanceof EntityFieldFallbackValue
                        && $value->getFallback() === 'category';
                })
            );

        $result = $this->populator->populate($product);

        self::assertTrue($result);
    }

    public function testPopulateWithMultipleFieldsAndFallbacks(): void
    {
        $product = new Product();
        $existingFallback = new EntityFieldFallbackValue();

        $this->fieldProvider->expects(self::once())
            ->method('getFieldsByFallbackId')
            ->willReturn([
                'category' => ['pageTemplate', 'metaTitle'],
                'systemConfig' => ['minimumQuantityToOrder', 'maximumQuantityToOrder']
            ]);

        $this->propertyAccessor->expects(self::exactly(4))
            ->method('getValue')
            ->willReturnCallback(function ($prod, $field) use ($product, $existingFallback) {
                self::assertSame($product, $prod);
                // First field already has value
                return $field === 'pageTemplate' ? $existingFallback : null;
            });

        // Should set value for 3 fields (all except pageTemplate which already has value)
        $setValueCallCount = 0;
        $this->propertyAccessor->expects(self::exactly(3))
            ->method('setValue')
            ->willReturnCallback(function ($prod, $field, $value) use ($product, &$setValueCallCount) {
                self::assertSame($product, $prod);
                self::assertInstanceOf(EntityFieldFallbackValue::class, $value);

                // Verify correct fallback ID based on field
                if ($field === 'metaTitle') {
                    self::assertSame('category', $value->getFallback());
                } elseif (in_array($field, ['minimumQuantityToOrder', 'maximumQuantityToOrder'], true)) {
                    self::assertSame('systemConfig', $value->getFallback());
                } else {
                    self::fail("Unexpected field: $field");
                }

                $setValueCallCount++;
            });

        $result = $this->populator->populate($product);

        self::assertTrue($result);
        self::assertSame(3, $setValueCallCount);
    }

    public function testPopulateReturnsTrueWhenAtLeastOneFieldIsPopulated(): void
    {
        $product = new Product();
        $existingFallback = new EntityFieldFallbackValue();

        $this->fieldProvider->expects(self::once())
            ->method('getFieldsByFallbackId')
            ->willReturn([
                'category' => ['pageTemplate', 'metaTitle']
            ]);

        $callCount = 0;
        $this->propertyAccessor->expects(self::exactly(2))
            ->method('getValue')
            ->willReturnCallback(function () use ($existingFallback, &$callCount) {
                $callCount++;
                return $callCount === 1 ? $existingFallback : null;
            });

        $this->propertyAccessor->expects(self::once())
            ->method('setValue')
            ->with(
                $product,
                'metaTitle',
                self::callback(function ($value) {
                    return $value instanceof EntityFieldFallbackValue
                        && $value->getFallback() === 'category';
                })
            );

        $result = $this->populator->populate($product);

        self::assertTrue($result);
    }
}
