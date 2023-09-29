<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model\ProductLineItemPrice;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\ProductBundle\Model\ProductLineItem;
use PHPUnit\Framework\TestCase;

class ProductLineItemPriceTest extends TestCase
{
    public function testGetters(): void
    {
        $lineItem = new ProductLineItem(42);
        $price = Price::create(12.3456, 'USD');
        $subtotal = 34.5678;
        $productLineItemPrice = new ProductLineItemPrice($lineItem, $price, $subtotal);

        self::assertSame($lineItem, $productLineItemPrice->getLineItem());
        self::assertSame($price, $productLineItemPrice->getPrice());
        self::assertSame($subtotal, $productLineItemPrice->getSubtotal());
    }
}
