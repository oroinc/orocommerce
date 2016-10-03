<?php

namespace Oro\Bundle\WarehouseBundle\Tests\Unit\ImportExport\Normalizer;

use Oro\Bundle\CurrencyBundle\Rounding\QuantityRoundingService;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\ImportExport\Serializer\InventoryLevelNormalizer;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use Oro\Bundle\WarehouseBundle\Entity\Warehouse;

class WarehouseInventoryLevelNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WarehouseInventoryLevelNormalizer
     */
    protected $warehouseInventoryLevelNormalizer;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProductUnitLabelFormatter */
    protected $formatter;

    /** @var  \PHPUnit_Framework_MockObject_MockObject|QuantityRoundingService */
    protected $roundingService;

    protected function setUp()
    {
        $this->formatter = $this->getMockBuilder(ProductUnitLabelFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->roundingService = $this->getMockBuilder(QuantityRoundingService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->warehouseInventoryLevelNormalizer = new InventoryLevelNormalizer(
            $this->formatter,
            $this->roundingService
        );
    }

    /**
     * @dataProvider getNormalizerData
     * @param int $quantity
     * @param string $wareHouseName
     * @param string $productUnitCode
     */
    public function testNormalizeShouldGenerateCorrectArray($quantity, $wareHouseName, $productUnitCode)
    {
        $object = new InventoryLevel();
        $object->setQuantity($quantity);

        $wareHouse = new Warehouse();
        $wareHouse->setName($wareHouseName);
        $object->setWarehouse($wareHouse);

        $unitPrecision = new ProductUnitPrecision();
        $productUnit = new ProductUnit();
        $productUnit->setCode($productUnitCode);
        $unitPrecision->setUnit($productUnit);
        $object->setProductUnitPrecision($unitPrecision);
        $this->formatter->expects($this->any())
            ->method('format')
            ->willReturn('testCode');
        $this->roundingService->expects($this->any())
            ->method('roundQuantity')
            ->willReturn($quantity);

        $results = $this->warehouseInventoryLevelNormalizer->normalize($object);
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
        $object = new InventoryLevel();
        $results = $this->warehouseInventoryLevelNormalizer->normalize($object);
        $this->assertNull($results['quantity']);
    }
}
