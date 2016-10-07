<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\ImportExport\Normalizer;

use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;

class InventoryLevelNormalizerTest extends BaseInventoryLevelNormalizerTestCase
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

        $results = $this->inventoryLevelNormalizer->normalize($object);
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
        $object = new InventoryLevel();
        $results = $this->inventoryLevelNormalizer->normalize($object);
        $this->assertNull($results['quantity']);
    }
}
