<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model\DTO;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\TestCase;

class ProductPriceDTOTest extends TestCase
{
    public function testGetters(): void
    {
        $product = (new ProductStub())->setId(42);
        $productUnit = (new ProductUnit())->setCode('item');
        $price = Price::create(12.3456, 'USD');
        $quantity = 34.5678;
        $productPriceDTO = new ProductPriceDTO($product, $price, $quantity, $productUnit);

        self::assertSame($product, $productPriceDTO->getProduct());
        self::assertSame($price, $productPriceDTO->getPrice());
        self::assertSame($quantity, $productPriceDTO->getQuantity());
        self::assertSame($productUnit, $productPriceDTO->getUnit());
        self::assertSame(
            [
                ProductPriceDTO::PRICE => $price->getValue(),
                ProductPriceDTO::CURRENCY => $price->getCurrency(),
                ProductPriceDTO::QUANTITY => $quantity,
                ProductPriceDTO::UNIT => $productUnit->getCode(),
                ProductPriceDTO::PRODUCT => $product->getId(),
            ],
            $productPriceDTO->toArray()
        );
    }
}
