<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\ProductKit\ProductPrice;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\ProductKit\ProductPrice\ProductKitItemPriceDTO;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\TestCase;

class ProductKitItemPriceDTOTest extends TestCase
{
    public function testGetters(): void
    {
        $productKitItem = new ProductKitItemStub(10);
        $product = (new ProductStub())->setId(42);
        $productUnit = (new ProductUnit())->setCode('item');
        $price = Price::create(12.3456, 'USD');
        $quantity = 34.5678;
        $productKitItemPriceDTO = new ProductKitItemPriceDTO(
            $productKitItem,
            $product,
            $price,
            $quantity,
            $productUnit
        );

        self::assertSame($productKitItem, $productKitItemPriceDTO->getKitItem());
        self::assertSame($product, $productKitItemPriceDTO->getProduct());
        self::assertSame($price, $productKitItemPriceDTO->getPrice());
        self::assertSame($quantity, $productKitItemPriceDTO->getQuantity());
        self::assertSame($productUnit, $productKitItemPriceDTO->getUnit());
        self::assertSame(
            [
                ProductPriceDTO::PRICE => $price->getValue(),
                ProductPriceDTO::CURRENCY => $price->getCurrency(),
                ProductPriceDTO::QUANTITY => $quantity,
                ProductPriceDTO::UNIT => $productUnit->getCode(),
                ProductPriceDTO::PRODUCT => $product->getId(),
                ProductKitItemPriceDTO::PRODUCT_KIT_ITEM_ID => $productKitItem->getId(),
            ],
            $productKitItemPriceDTO->toArray()
        );
    }
}
