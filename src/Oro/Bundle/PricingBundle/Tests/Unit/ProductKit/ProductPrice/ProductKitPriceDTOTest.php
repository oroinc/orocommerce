<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\ProductKit\ProductPrice;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\ProductKit\ProductPrice\ProductKitItemPriceDTO;
use Oro\Bundle\PricingBundle\ProductKit\ProductPrice\ProductKitPriceDTO;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\TestCase;

class ProductKitPriceDTOTest extends TestCase
{
    public function testGetters(): void
    {
        $productKit = (new ProductStub())->setId(42)->setType(Product::TYPE_KIT);
        $productUnitItem = (new ProductUnit())->setCode('item');
        $price = Price::create(12.3456, 'USD');
        $quantity = 34.5678;
        $productKitPriceDTO = new ProductKitPriceDTO($productKit, $price, $quantity, $productUnitItem);

        self::assertSame($productKit, $productKitPriceDTO->getProduct());
        self::assertSame($price, $productKitPriceDTO->getPrice());
        self::assertSame($quantity, $productKitPriceDTO->getQuantity());
        self::assertSame($productUnitItem, $productKitPriceDTO->getUnit());
        self::assertSame([], $productKitPriceDTO->getKitItemPrices());
        self::assertSame(
            [
                ProductPriceDTO::PRICE => $price->getValue(),
                ProductPriceDTO::CURRENCY => $price->getCurrency(),
                ProductPriceDTO::QUANTITY => $quantity,
                ProductPriceDTO::UNIT => $productUnitItem->getCode(),
                ProductPriceDTO::PRODUCT => $productKit->getId(),
                ProductKitPriceDTO::KIT_ITEMS_PRICES => [],
            ],
            $productKitPriceDTO->toArray()
        );

        $productKitItem1 = new ProductKitItemStub(10);
        $kitItemProduct1 = (new ProductStub())->setId(100);
        $productUnitEach = (new ProductUnit())->setCode('each');
        $kitItem1Price = Price::create(1.2345, 'USD');
        $kitItem1Quantity = 10.2345;
        $productKitItem1PriceDTO = new ProductKitItemPriceDTO(
            $productKitItem1,
            $kitItemProduct1,
            $kitItem1Price,
            $kitItem1Quantity,
            $productUnitEach
        );

        self::assertNull($productKitPriceDTO->getKitItemPrice($productKitItem1));

        $productKitPriceDTO->addKitItemPrice($productKitItem1PriceDTO);
        self::assertSame(
            [$productKitItem1->getId() => $productKitItem1PriceDTO],
            $productKitPriceDTO->getKitItemPrices()
        );
        self::assertSame($productKitItem1PriceDTO, $productKitPriceDTO->getKitItemPrice($productKitItem1));
        self::assertSame(
            [
                ProductPriceDTO::PRICE => $price->getValue(),
                ProductPriceDTO::CURRENCY => $price->getCurrency(),
                ProductPriceDTO::QUANTITY => $quantity,
                ProductPriceDTO::UNIT => $productUnitItem->getCode(),
                ProductPriceDTO::PRODUCT => $productKit->getId(),
                ProductKitPriceDTO::KIT_ITEMS_PRICES => [
                    $productKitItem1->getId() => [
                        ProductPriceDTO::PRICE => $kitItem1Price->getValue(),
                        ProductPriceDTO::CURRENCY => $kitItem1Price->getCurrency(),
                        ProductPriceDTO::QUANTITY => $kitItem1Quantity,
                        ProductPriceDTO::UNIT => $productUnitEach->getCode(),
                        ProductPriceDTO::PRODUCT => $kitItemProduct1->getId(),
                        ProductKitItemPriceDTO::PRODUCT_KIT_ITEM_ID => $productKitItem1->getId(),
                    ],
                ],
            ],
            $productKitPriceDTO->toArray()
        );

        $productKitPriceDTO->removeKitItemPrice($productKitItem1PriceDTO);
        self::assertSame([], $productKitPriceDTO->getKitItemPrices());
    }
}
