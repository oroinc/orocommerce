<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Model;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Model\ProductLineItem;

class ProductLineItemTest extends \PHPUnit_Framework_TestCase
{
    const IDENTIFIER = 'identifier';
    const UNIT_CODE = 'unit_code';
    const PRODUCT_SKU = 'product_sku';

    use EntityTestCaseTrait;

    public function testProperties()
    {
        $unit = new ProductUnit();
        $unit->setCode(self::UNIT_CODE);
        $product = new Product();
        $product->setSku(self::PRODUCT_SKU);
        $properties = [
            ['unit', $unit],
            ['quantity', 5],
            ['product', $product],
        ];
        $lineItem = new ProductLineItem(self::IDENTIFIER);
        $this->assertPropertyAccessors($lineItem, $properties);
        $this->assertEquals(self::IDENTIFIER, $lineItem->getEntityIdentifier());
        $this->assertEquals($lineItem, $lineItem->getProductHolder());

        $lineItem->setUnit($unit);
        $this->assertEquals(self::UNIT_CODE, $lineItem->getProductUnitCode());
        $lineItem->setProduct($product);
        $this->assertEquals(self::PRODUCT_SKU, $lineItem->getProductSku());
    }
}
