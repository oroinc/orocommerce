<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model\ProductLineItemPrice\Factory;

use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\Factory\ProductLineItemPriceFactory;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\Factory\ProductLineItemPriceFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductLineItemPriceFactoryTest extends TestCase
{
    private ProductLineItemPriceFactoryInterface|MockObject $innerFactory1;

    private ProductLineItemPriceFactoryInterface|MockObject $innerFactory2;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerFactory1 = $this->createMock(ProductLineItemPriceFactoryInterface::class);
        $this->innerFactory2 = $this->createMock(ProductLineItemPriceFactoryInterface::class);
    }

    public function testIsSupportedNoInnerFactories(): void
    {
        self::assertFalse(
            (new ProductLineItemPriceFactory([]))->isSupported(
                $this->createMock(ProductLineItemInterface::class),
                $this->createMock(ProductPriceInterface::class)
            )
        );
    }

    public function testCreateForProductLineItemWhenNoInnerFactories(): void
    {
        self::assertNull(
            (new ProductLineItemPriceFactory([]))->createForProductLineItem(
                $this->createMock(ProductLineItemInterface::class),
                $this->createMock(ProductPriceInterface::class)
            )
        );
    }

    public function testIsSupportedHasInnerFactories(): void
    {
        $productLineItemPriceFactory = new ProductLineItemPriceFactory(
            [$this->innerFactory1, $this->innerFactory2]
        );
        $lineItem = $this->createMock(ProductLineItemInterface::class);
        $productPrice = $this->createMock(ProductPriceInterface::class);

        $this->innerFactory1
            ->expects(self::once())
            ->method('isSupported')
            ->with($lineItem, $productPrice)
            ->willReturn(false);

        $this->innerFactory2
            ->expects(self::once())
            ->method('isSupported')
            ->with($lineItem, $productPrice)
            ->willReturn(true);

        self::assertTrue($productLineItemPriceFactory->isSupported($lineItem, $productPrice));
    }

    public function testCreateForProductLineItemWhenHasInnerFactories(): void
    {
        $productLineItemPriceFactory = new ProductLineItemPriceFactory(
            [$this->innerFactory1, $this->innerFactory2]
        );
        $lineItem = $this->createMock(ProductLineItemInterface::class);
        $productPrice = $this->createMock(ProductPriceInterface::class);

        $this->innerFactory1
            ->expects(self::once())
            ->method('isSupported')
            ->with($lineItem, $productPrice)
            ->willReturn(true);
        $productLineItemPrice = $this->createMock(ProductLineItemPrice::class);
        $this->innerFactory1
            ->expects(self::once())
            ->method('createForProductLineItem')
            ->with($lineItem, $productPrice)
            ->willReturn($productLineItemPrice);

        $this->innerFactory2
            ->expects(self::never())
            ->method(self::anything());

        self::assertSame(
            $productLineItemPrice,
            $productLineItemPriceFactory->createForProductLineItem($lineItem, $productPrice)
        );
    }
}
