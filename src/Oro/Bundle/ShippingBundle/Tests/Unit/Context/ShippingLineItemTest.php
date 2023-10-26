<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingKitItemLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;

class ShippingLineItemTest extends AbstractShippingLineItemTest
{
    public function testGetters(): void
    {
        $anotherQuantity = 123.123;
        $checksum = 'checksum_1';
        $anotherSku = 'anotherSku';
        $shippingKitItemLineItems = new ArrayCollection([$this->createMock(ShippingKitItemLineItem::class)]);
        $shippingLineItemParams = $this->getShippingLineItemParams();

        $shippingLineItem = new ShippingLineItem($shippingLineItemParams);
        $shippingLineItem
            ->setProductSku($anotherSku)
            ->setKitItemLineItems($shippingKitItemLineItems)
            ->setChecksum($checksum)
            ->setQuantity($anotherQuantity);

        self::assertSame($this->productUnit, $shippingLineItem->getProductUnit());
        self::assertEquals(self::TEST_UNIT_CODE, $shippingLineItem->getProductUnitCode());
        self::assertEquals($anotherQuantity, $shippingLineItem->getQuantity());
        self::assertSame($this->productHolder, $shippingLineItem->getProductHolder());
        self::assertEquals($this->productHolder->getEntityIdentifier(), $shippingLineItem->getEntityIdentifier());
        self::assertSame($this->product, $shippingLineItem->getProduct());
        self::assertEquals($anotherSku, $shippingLineItem->getProductSku());
        self::assertSame($this->price, $shippingLineItem->getPrice());
        self::assertSame($this->dimensions, $shippingLineItem->getDimensions());
        self::assertSame($this->weight, $shippingLineItem->getWeight());
        self::assertSame($shippingKitItemLineItems, $shippingLineItem->getKitItemLineItems());
        self::assertEquals($checksum, $shippingLineItem->getChecksum());
    }
}
