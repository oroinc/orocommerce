<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model\ProductLineItemPrice\Factory;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\Factory\SimpleProductLineItemPriceFactory;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductLineItem;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\TestCase;

class SimpleProductLineItemPriceFactoryTest extends TestCase
{
    public const USD = 'USD';

    private SimpleProductLineItemPriceFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService
            ->method('round')
            ->willReturnCallback(static fn ($value) => round($value, 2));

        $this->factory = new SimpleProductLineItemPriceFactory($roundingService);
    }

    public function testIsSupported(): void
    {
        self::assertTrue(
            $this->factory->isSupported(
                $this->createMock(ProductLineItemInterface::class),
                $this->createMock(ProductPriceInterface::class)
            )
        );
    }

    public function testCreateForProductLineItem(): void
    {
        $productUnitItem = (new ProductUnit())->setCode('item');
        $product = (new ProductStub())->setId(100);
        $kitLineItem = (new ProductLineItem(42))
            ->setProduct($product)
            ->setUnit($productUnitItem)
            ->setQuantity(111);
        $productPrice = new ProductPriceDTO($product, Price::create(12.345, self::USD), 1, $productUnitItem);

        $productLineItemPrice = new ProductLineItemPrice($kitLineItem, Price::create(12.345, self::USD), 1370.3);

        self::assertEquals(
            $productLineItemPrice,
            $this->factory->createForProductLineItem($kitLineItem, $productPrice)
        );
    }
}
