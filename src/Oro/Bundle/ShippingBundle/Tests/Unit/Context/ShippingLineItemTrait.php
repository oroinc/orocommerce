<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use PHPUnit\Framework\MockObject\MockObject;

trait ShippingLineItemTrait
{
    private const LINE_ITEM_UNIT_CODE = 'item';
    private const LINE_ITEM_QUANTITY = 15;
    private const LINE_ITEM_ENTITY_ID = 1;

    protected ProductUnit|MockObject $productUnitMock;

    protected ProductHolderInterface|MockObject $productHolderMock;

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
}
