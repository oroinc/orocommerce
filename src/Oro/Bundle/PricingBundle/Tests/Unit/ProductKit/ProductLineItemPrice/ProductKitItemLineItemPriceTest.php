<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\ProductKit\ProductLineItemPrice;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitItemLineItemPrice;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemStub;
use PHPUnit\Framework\TestCase;

class ProductKitItemLineItemPriceTest extends TestCase
{
    public function testGetters(): void
    {
        $kitItemLineItem = new ProductKitItemLineItemStub(42);
        $price = Price::create(12.3456, 'USD');
        $subtotal = 34.5678;
        $kitItemLineItemPrice = new ProductKitItemLineItemPrice($kitItemLineItem, $price, $subtotal);

        self::assertSame($kitItemLineItem, $kitItemLineItemPrice->getLineItem());
        self::assertSame($kitItemLineItem, $kitItemLineItemPrice->getKitItemLineItem());
        self::assertSame($price, $kitItemLineItemPrice->getPrice());
        self::assertSame($subtotal, $kitItemLineItemPrice->getSubtotal());
    }
}
