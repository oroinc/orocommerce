<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Unit\ImportExport\Normalizer;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;
use OroB2B\Bundle\WarehouseBundle\ImportExport\Normalizer\InventoryLevelNormalizer;

class InventoryLevelNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InventoryLevelNormalizer
     */
    protected $inventoryLevelNormalizer;

    protected function setUp()
    {
        $this->inventoryLevelNormalizer = new InventoryLevelNormalizer();
    }

    /**
     * @dataProvider getNormalizerData
     * @param int $quantity
     * @param string $wareHouseName
     * @param string $productUnitCode
     */
    public function testNormalizeShouldGenerateCorrectArray($quantity, $wareHouseName, $productUnitCode)
    {
        $object = new WarehouseInventoryLevel();
        $object->setQuantity($quantity);

        $wareHouse = new Warehouse();
        $wareHouse->setName($wareHouseName);
        $object->setWarehouse($wareHouse);

        $unitPrecision = new ProductUnitPrecision();
        $productUnit = new ProductUnit();
        $productUnit->setCode($productUnitCode);
        $unitPrecision->setUnit($productUnit);
        $object->setProductUnitPrecision($unitPrecision);

        $results = $this->inventoryLevelNormalizer->normalize($object);
        $this->assertArrayHasKey('warehouse', $results);
        $this->assertEquals('testCode', $results['productUnitPrecision']['unit']['code']);
        $this->assertEquals('testName', $results['warehouse']['name']);
        $this->assertEquals($object->getQuantity(), $results['quantity']);
    }

    public function getNormalizerData()
    {
        return [
            [5, 'testName', 'testCode']
        ];
    }

    public function testNormalizeShouldIgnoreZeroQuantity()
    {
        $object = new WarehouseInventoryLevel();
        $results = $this->inventoryLevelNormalizer->normalize($object);
        $this->assertNull($results['quantity']);
    }
}
