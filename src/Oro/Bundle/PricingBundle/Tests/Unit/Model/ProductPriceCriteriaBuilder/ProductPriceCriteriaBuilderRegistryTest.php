<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model\ProductPriceCriteriaBuilder;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaBuilder\ProductPriceCriteriaBuilderInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaBuilder\ProductPriceCriteriaBuilderRegistry;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductPriceCriteriaBuilderRegistryTest extends TestCase
{
    private ProductPriceCriteriaBuilderInterface|MockObject $builder1;

    private ProductPriceCriteriaBuilderInterface|MockObject $builder2;

    #[\Override]
    protected function setUp(): void
    {
        $this->builder1 = $this->createMock(ProductPriceCriteriaBuilderInterface::class);
        $this->builder2 = $this->createMock(ProductPriceCriteriaBuilderInterface::class);
    }

    public function testGetBuilderForProductWhenNoInnerBuilders(): void
    {
        $product = (new ProductStub())->setId(42);

        $this->expectExceptionObject(new \LogicException(
            sprintf('No applicable product price criteria builder is found for product #%d', $product->getId())
        ));

        (new ProductPriceCriteriaBuilderRegistry([]))->getBuilderForProduct($product);
    }

    public function testGetBuilderForProductWhenHasInnerBuilders(): void
    {
        $registry = new ProductPriceCriteriaBuilderRegistry(
            [$this->builder1, $this->builder2]
        );
        $product = (new ProductStub())->setId(42);

        $this->builder1
            ->expects(self::once())
            ->method('isSupported')
            ->with($product)
            ->willReturn(true);

        $this->builder2
            ->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            $this->builder1,
            $registry->getBuilderForProduct($product)
        );
    }
}
