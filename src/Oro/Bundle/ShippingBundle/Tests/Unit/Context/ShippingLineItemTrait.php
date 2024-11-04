<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingKitItemLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use PHPUnit\Framework\MockObject\MockObject;

trait ShippingLineItemTrait
{
    private const LINE_ITEM_UNIT_CODE = 'item';
    private const LINE_ITEM_QUANTITY = 15;
    private const LINE_ITEM_ENTITY_ID = 1;
    private const KIT_ITEM_LINE_ITEM_ENTITY_ID = 2;

    protected ProductUnit|MockObject $productUnitMock;

    public function getShippingLineItem(
        ?ProductUnit $productUnit = null,
        ?float $quantity = null,
        ?string $unitCode = null
    ): ShippingLineItem {
        if ($productUnit === null) {
            $productUnit = $this->createMock(ProductUnit::class);
            $productUnit->method('getCode')->willReturn($unitCode ?? static::LINE_ITEM_UNIT_CODE);
        }

        $productHolder = $this->createMock(ProductHolderInterface::class);
        $productHolder->method('getEntityIdentifier')->willReturn(static::LINE_ITEM_ENTITY_ID);

        return new ShippingLineItem(
            $productUnit,
            $quantity ?? static::LINE_ITEM_QUANTITY,
            $productHolder
        );
    }

    public function getShippingKitItemLineItem(
        ?Product $product,
        ?Price $price,
        ?float $quantity = null,
        ?string $unitCode = null,
        int $precision = 0,
        int $defaultPrecision = 0
    ): ShippingKitItemLineItem {
        $unit = (new ProductUnit())->setCode($unitCode)->setDefaultPrecision($defaultPrecision);
        $unitPrecision = (new ProductUnitPrecision())->setPrecision($precision)->setUnit($unit)->setProduct($product);
        $product->addUnitPrecision($unitPrecision);

        $productKitItemProduct = (new ProductKitItemProduct())->setProduct($product);
        $kitItem = (new ProductKitItem())->setProductUnit($unit)->addKitItemProduct($productKitItemProduct);
        $productKitItemProduct->setKitItem($kitItem);

        $productHolder = $this->createMock(ProductHolderInterface::class);
        $productHolder->method('getEntityIdentifier')->willReturn(static::KIT_ITEM_LINE_ITEM_ENTITY_ID);

        return (new ShippingKitItemLineItem($productHolder))
            ->setQuantity($quantity)
            ->setProductUnit($unit)
            ->setProductUnitCode($unitCode)
            ->setKitItem($kitItem)
            ->setPrice($price);
    }
}
