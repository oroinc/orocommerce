<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\ProductKit\ProductLineItemPrice\Factory;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\Factory\ProductKitItemLineItemPriceFactory;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitItemLineItemPrice;
use Oro\Bundle\PricingBundle\ProductKit\ProductPrice\ProductKitItemPriceDTO;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\TestCase;

class ProductKitItemLineItemPriceFactoryTest extends TestCase
{
    private ProductKitItemLineItemPriceFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService
            ->method('round')
            ->willReturnCallback(static fn ($value) => round($value, 2));

        $this->factory = new ProductKitItemLineItemPriceFactory($roundingService);
    }

    /**
     * @dataProvider isSupportedDataProvider
     */
    public function testIsSupported(
        ProductLineItemInterface $lineItem,
        ProductPriceInterface $productPrice,
        bool $expected
    ): void {
        self::assertEquals($expected, $this->factory->isSupported($lineItem, $productPrice));
    }

    public function isSupportedDataProvider(): array
    {
        return [
            'not supported' => [
                'lineItem' => $this->createMock(ProductLineItemInterface::class),
                'productPrice' => $this->createMock(ProductPriceInterface::class),
                'expected' => false,
            ],
            'supported' => [
                'lineItem' => $this->createMock(ProductKitItemLineItemInterface::class),
                'productPrice' => $this->createMock(ProductKitItemPriceDTO::class),
                'expected' => true,
            ],
        ];
    }

    public function testCreateForProductLineItemWhenNotSupported(): void
    {
        self::assertNull(
            $this->factory->createForProductLineItem(
                $this->createMock(ProductLineItemInterface::class),
                $this->createMock(ProductPriceInterface::class)
            )
        );
    }

    public function testCreateForProductLineItemWhenSupported(): void
    {
        $product = (new ProductStub())
            ->setId(10);
        $kitItem = new ProductKitItemStub(42);
        $kitItemLineItem = (new ProductKitItemLineItemStub(4242))
            ->setKitItem($kitItem)
            ->setQuantity(12.345);
        $price = Price::create(11.234, 'USD');
        $productUnit = (new ProductUnit())
            ->setCode('item');
        $productPrice = new ProductKitItemPriceDTO($kitItem, $product, $price, 1, $productUnit);

        self::assertEquals(
            new ProductKitItemLineItemPrice(
                $kitItemLineItem,
                $price,
                138.68
            ),
            $this->factory->createForProductLineItem(
                $kitItemLineItem,
                $productPrice
            )
        );
    }
}
