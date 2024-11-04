<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model\ProductLineItemPriceCriteriaFactory;

use Oro\Bundle\PricingBundle\Model\ProductLineItemPriceCriteriaFactory\ProductLineItemPriceCriteriaFactory;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPriceCriteriaFactory\ProductLineItemPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductLineItemPriceCriteriaFactoryTest extends TestCase
{
    private const USD = 'USD';

    private ProductLineItemPriceCriteriaFactoryInterface|MockObject $innerFactory1;

    private ProductLineItemPriceCriteriaFactoryInterface|MockObject $innerFactory2;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerFactory1 = $this->createMock(ProductLineItemPriceCriteriaFactoryInterface::class);
        $this->innerFactory2 = $this->createMock(ProductLineItemPriceCriteriaFactoryInterface::class);
    }

    public function testIsSupportedNoInnerFactories(): void
    {
        self::assertFalse(
            (new ProductLineItemPriceCriteriaFactory([]))->isSupported(
                $this->createMock(ProductLineItemInterface::class),
                null
            )
        );
    }

    public function testCreateFromProductLineItemWhenNoInnerFactories(): void
    {
        self::assertNull(
            (new ProductLineItemPriceCriteriaFactory([]))->createFromProductLineItem(
                $this->createMock(ProductLineItemInterface::class),
                null
            )
        );
    }

    public function testIsSupportedHasInnerFactories(): void
    {
        $productLineItemPriceCriteriaFactory = new ProductLineItemPriceCriteriaFactory(
            [$this->innerFactory1, $this->innerFactory2]
        );
        $lineItem = $this->createMock(ProductLineItemInterface::class);

        $this->innerFactory1
            ->expects(self::once())
            ->method('isSupported')
            ->with($lineItem, self::USD)
            ->willReturn(false);

        $this->innerFactory2
            ->expects(self::once())
            ->method('isSupported')
            ->with($lineItem, self::USD)
            ->willReturn(true);

        self::assertTrue($productLineItemPriceCriteriaFactory->isSupported($lineItem, self::USD));
    }

    public function testCreateFromProductLineItemWhenHasInnerFactories(): void
    {
        $productLineItemPriceCriteriaFactory = new ProductLineItemPriceCriteriaFactory(
            [$this->innerFactory1, $this->innerFactory2]
        );
        $lineItem = $this->createMock(ProductLineItemInterface::class);
        $this->innerFactory1
            ->expects(self::once())
            ->method('isSupported')
            ->with($lineItem, self::USD)
            ->willReturn(true);
        $productPriceCriteria = $this->createMock(ProductPriceCriteria::class);
        $this->innerFactory1
            ->expects(self::once())
            ->method('createFromProductLineItem')
            ->with($lineItem, self::USD)
            ->willReturn($productPriceCriteria);

        $this->innerFactory2
            ->expects(self::never())
            ->method(self::anything());

        self::assertSame(
            $productPriceCriteria,
            $productLineItemPriceCriteriaFactory->createFromProductLineItem($lineItem, self::USD)
        );
    }
}
