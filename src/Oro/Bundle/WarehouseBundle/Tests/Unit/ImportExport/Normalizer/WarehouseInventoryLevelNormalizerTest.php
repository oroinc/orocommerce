<?php

namespace Oro\Bundle\WarehouseBundle\Tests\Unit\ImportExport\Normalizer;

use Oro\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class WarehouseInventoryLevelNormalizerTest extends BaseInventoryLevelNormalizerTestCase
{

    /**
     * @dataProvider getNormalizerData
     * @param int $quantity
     * @param string $productUnitCode
     */
    public function testNormalizeShouldGenerateCorrectArray($quantity, $productUnitCode)
    {
        $object = $this->getInventoryLevelEntity($quantity, $productUnitCode);
        $this->formatter->expects($this->any())
            ->method('format')
            ->willReturn('testCode');
        $this->roundingService->expects($this->any())
            ->method('roundQuantity')
            ->willReturn($quantity);

        $results = $this->warehouseInventoryLevelNormalizer->normalize($object);
        $this->assertArrayHasKey('warehouse', $results);
        $this->assertEquals('testCode', $results['productUnitPrecision']['unit']['code']);
        $this->assertEquals($object->getQuantity(), $results['quantity']);
    }

    public function getNormalizerData()
    {
        return [
            [5, 'testCode']
        ];
    }

    public function testNormalizeShouldIgnoreZeroQuantity()
    {
        $object = new WarehouseInventoryLevel();
        $results = $this->warehouseInventoryLevelNormalizer->normalize($object);
        $this->assertNull($results['quantity']);
    }
}
