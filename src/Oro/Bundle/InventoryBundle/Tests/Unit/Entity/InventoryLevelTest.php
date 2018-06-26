<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Entity;

use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class InventoryLevelTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '123'],
            ['quantity', 10.55],
            ['product', new Product()],
            ['productUnitPrecision', new ProductUnitPrecision()]
        ];

        $inventoryLevel = new InventoryLevel();
        $this->assertPropertyAccessors($inventoryLevel, $properties);
    }

    public function testSetProductUnitPrecision()
    {
        $product = new Product();
        $productUnitPrecision = new ProductUnitPrecision();
        $productUnitPrecision->setProduct($product);

        $warehouseInventoryLevel = new InventoryLevel();
        $this->assertEmpty($warehouseInventoryLevel->getProduct());
        $this->assertEmpty($warehouseInventoryLevel->getProductUnitPrecision());

        $warehouseInventoryLevel->setProductUnitPrecision($productUnitPrecision);
        $this->assertEquals($productUnitPrecision, $warehouseInventoryLevel->getProductUnitPrecision());
        $this->assertEquals($product, $warehouseInventoryLevel->getProduct());
    }
}
