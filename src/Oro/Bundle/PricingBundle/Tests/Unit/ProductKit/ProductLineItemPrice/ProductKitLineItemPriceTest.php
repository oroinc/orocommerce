<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\ProductKit\ProductLineItemPrice;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitItemLineItemPrice;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitLineItemPrice;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemsAwareStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemStub;
use PHPUnit\Framework\TestCase;

class ProductKitLineItemPriceTest extends TestCase
{
    public function testGetters(): void
    {
        $price = Price::create(12.3456, 'USD');
        $subtotal = 34.5678;
        $kitLineItem = new ProductKitItemLineItemsAwareStub(42);
        $kitLineItemPrice = new ProductKitLineItemPrice($kitLineItem, $price, $subtotal);

        self::assertSame($kitLineItem, $kitLineItemPrice->getLineItem());
        self::assertSame($price, $kitLineItemPrice->getPrice());
        self::assertSame($subtotal, $kitLineItemPrice->getSubtotal());
        self::assertSame([], $kitLineItemPrice->getKitItemLineItemPrices());

        $kitItem1 = new ProductKitItemStub(100);
        $kitItemLineItem1 = (new ProductKitItemLineItemStub(10))
            ->setKitItem($kitItem1);
        $kitItemLineItem1Price = Price::create(1.2345, 'USD');
        $kitItemLineItem1Subtotal = 2.3456;
        $kitItemLineItem1Price = new ProductKitItemLineItemPrice(
            $kitItemLineItem1,
            $kitItemLineItem1Price,
            $kitItemLineItem1Subtotal
        );

        $kitLineItemPrice->addKitItemLineItemPrice($kitItemLineItem1Price);

        self::assertSame(
            [$kitItem1->getId() => $kitItemLineItem1Price],
            $kitLineItemPrice->getKitItemLineItemPrices()
        );

        $kitItem2 = new ProductKitItemStub(200);
        $kitItemLineItem2 = (new ProductKitItemLineItemStub(20))
            ->setKitItem($kitItem2);
        $kitItemLineItem2Price = Price::create(2.3456, 'USD');
        $kitItemLineItem2Subtotal = 3.4567;
        $kitItemLineItem2Price = new ProductKitItemLineItemPrice(
            $kitItemLineItem2,
            $kitItemLineItem2Price,
            $kitItemLineItem2Subtotal
        );

        $kitLineItemPrice->addKitItemLineItemPrice($kitItemLineItem2Price);

        self::assertSame(
            [
                $kitItem1->getId() => $kitItemLineItem1Price,
                $kitItem2->getId() => $kitItemLineItem2Price,
            ],
            $kitLineItemPrice->getKitItemLineItemPrices()
        );
    }

    public function testAddKitItemLineItemPriceWhenAlreadyExists(): void
    {
        $price = Price::create(12.3456, 'USD');
        $subtotal = 34.5678;
        $kitLineItem = new ProductKitItemLineItemsAwareStub(42);
        $kitLineItemPrice = new ProductKitLineItemPrice($kitLineItem, $price, $subtotal);

        $kitItem1 = new ProductKitItemStub(100);
        $kitItemLineItem1 = (new ProductKitItemLineItemStub(10))
            ->setKitItem($kitItem1);
        $kitItemLineItem1Price = Price::create(1.2345, 'USD');
        $kitItemLineItem1Subtotal = 2.3456;
        $kitItemLineItem1Price = new ProductKitItemLineItemPrice(
            $kitItemLineItem1,
            $kitItemLineItem1Price,
            $kitItemLineItem1Subtotal
        );

        $kitLineItemPrice->addKitItemLineItemPrice($kitItemLineItem1Price);

        $kitItemLineItem2Price = Price::create(2.2345, 'USD');
        $kitItemLineItem1Subtotal = 3.3456;
        $kitItemLineItem2Price = new ProductKitItemLineItemPrice(
            $kitItemLineItem1,
            $kitItemLineItem2Price,
            $kitItemLineItem1Subtotal
        );
        $kitLineItemPrice->addKitItemLineItemPrice($kitItemLineItem2Price);

        self::assertSame(
            [
                $kitItem1->getId() => $kitItemLineItem2Price,
            ],
            $kitLineItemPrice->getKitItemLineItemPrices()
        );
    }

    public function testGetKitItemLineItemPrice(): void
    {
        $price = Price::create(12.3456, 'USD');
        $subtotal = 34.5678;
        $kitLineItem = new ProductKitItemLineItemsAwareStub(42);
        $kitLineItemPrice = new ProductKitLineItemPrice($kitLineItem, $price, $subtotal);

        $kitItem1 = new ProductKitItemStub(100);
        $kitItemLineItem1 = (new ProductKitItemLineItemStub(10))
            ->setKitItem($kitItem1);
        $kitItemLineItem1Price = Price::create(1.2345, 'USD');
        $kitItemLineItem1Subtotal = 2.3456;
        $kitItemLineItem1Price = new ProductKitItemLineItemPrice(
            $kitItemLineItem1,
            $kitItemLineItem1Price,
            $kitItemLineItem1Subtotal
        );

        self::assertNull($kitLineItemPrice->getKitItemLineItemPrice($kitItemLineItem1));

        $kitLineItemPrice->addKitItemLineItemPrice($kitItemLineItem1Price);

        self::assertSame(
            $kitItemLineItem1Price,
            $kitLineItemPrice->getKitItemLineItemPrice($kitItemLineItem1)
        );
    }
}
