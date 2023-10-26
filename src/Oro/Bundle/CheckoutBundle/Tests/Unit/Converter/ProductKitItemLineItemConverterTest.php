<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Converter;

use Oro\Bundle\CheckoutBundle\Converter\ProductKitItemLineItemConverter;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutProductKitItemLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use PHPUnit\Framework\TestCase;

class ProductKitItemLineItemConverterTest extends TestCase
{
    /**
     * @dataProvider getConvertDataProvider
     */
    public function testConvert(ProductKitItemLineItemInterface $kitItemLineItem): void
    {
        $expectedCheckoutProductKitItemLineItem = (new CheckoutProductKitItemLineItem())
            ->setProduct($kitItemLineItem->getProduct())
            ->setKitItem($kitItemLineItem->getKitItem())
            ->setProductUnit($kitItemLineItem->getProductUnit())
            ->setQuantity($kitItemLineItem->getQuantity())
            ->setSortOrder($kitItemLineItem->getSortOrder())
            ->setPriceFixed(false);

        $converter = new ProductKitItemLineItemConverter();

        self::assertEquals(
            $expectedCheckoutProductKitItemLineItem,
            $converter->convert($kitItemLineItem)
        );
    }

    public function getConvertDataProvider(): array
    {
        $product = (new ProductStub())->setId(1);
        $kitItem = new ProductKitItemStub();
        $productUnit = new ProductUnit();
        $quantity = 123.456;
        $sortOrder = 1;

        $orderProductKitItemLineItem = (new OrderProductKitItemLineItem())
            ->setProduct($product)
            ->setKitItem($kitItem)
            ->setProductUnit($productUnit)
            ->setQuantity($quantity)
            ->setSortOrder($sortOrder);

        $shoppingListProductKitItemLineItem = (new ProductKitItemLineItem())
            ->setProduct($product)
            ->setKitItem($kitItem)
            ->setUnit($productUnit)
            ->setQuantity($quantity)
            ->setSortOrder($sortOrder);

        return [
            'order product kit item line item' => [
                'kitItemLineItem' => $orderProductKitItemLineItem,
            ],
            'shopping list product kit item line item' => [
                'kitItemLineItem' => $shoppingListProductKitItemLineItem,
            ],
        ];
    }
}
