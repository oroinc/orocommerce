<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context\LineItem\Builder\Basic;

use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Basic\BasicShippingLineItemBuilder;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Tests\Unit\Context\AbstractShippingLineItemTest;

class BasicShippingLineItemBuilderTest extends AbstractShippingLineItemTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->productHolder->expects($this->once())
            ->method('getEntityIdentifier')
            ->willReturn(self::TEST_ENTITY_ID);
    }

    public function testFullBuild()
    {
        $builder = new BasicShippingLineItemBuilder(
            $this->productUnit,
            self::TEST_UNIT_CODE,
            self::TEST_QUANTITY,
            $this->productHolder
        );

        $builder
            ->setProduct($this->product)
            ->setPrice($this->price)
            ->setProductSku(self::TEST_PRODUCT_SKU)
            ->setDimensions($this->dimensions)
            ->setWeight($this->weight);

        $shippingLineItem = $builder->getResult();

        $expectedShippingLineItem = new ShippingLineItem($this->getShippingLineItemParams());

        $this->assertEquals($expectedShippingLineItem, $shippingLineItem);
    }

    public function testOptionalBuild()
    {
        $builder = new BasicShippingLineItemBuilder(
            $this->productUnit,
            self::TEST_UNIT_CODE,
            self::TEST_QUANTITY,
            $this->productHolder
        );

        $shippingLineItem = $builder->getResult();

        $expectedShippingLineItem = new ShippingLineItem([
            ShippingLineItem::FIELD_PRODUCT_UNIT => $this->productUnit,
            ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => self::TEST_UNIT_CODE,
            ShippingLineItem::FIELD_QUANTITY => self::TEST_QUANTITY,
            ShippingLineItem::FIELD_PRODUCT_HOLDER => $this->productHolder,
            ShippingLineItem::FIELD_ENTITY_IDENTIFIER => self::TEST_ENTITY_ID
        ]);

        $this->assertEquals($expectedShippingLineItem, $shippingLineItem);
    }
}
