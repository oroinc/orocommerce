<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\ImportExport\Normalizer;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\ImportExport\Serializer\InventoryLevelNormalizer;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Rounding\QuantityRoundingService;

class InventoryLevelNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var UnitLabelFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formatter;

    /** @var QuantityRoundingService|\PHPUnit\Framework\MockObject\MockObject */
    private $roundingService;

    /** @var FieldHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldHelper;

    /** @var InventoryLevelNormalizer */
    private $inventoryLevelNormalizer;

    protected function setUp(): void
    {
        $this->formatter = $this->createMock(UnitLabelFormatterInterface::class);
        $this->roundingService = $this->createMock(QuantityRoundingService::class);
        $this->fieldHelper = $this->createMock(FieldHelper::class);

        $this->inventoryLevelNormalizer = new InventoryLevelNormalizer(
            $this->fieldHelper,
            $this->formatter,
            $this->roundingService
        );
    }

    /**
     * @dataProvider getNormalizerData
     */
    public function testNormalizeShouldGenerateCorrectArray(int $quantity, string $productUnitCode)
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($productUnitCode);
        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit($productUnit);
        $object = new InventoryLevel();
        $object->setQuantity($quantity);
        $object->setProductUnitPrecision($unitPrecision);

        $this->formatter->expects($this->any())
            ->method('format')
            ->willReturn('testCode');
        $this->roundingService->expects($this->any())
            ->method('round')
            ->willReturn($quantity);

        $results = $this->inventoryLevelNormalizer->normalize($object);
        $this->assertEquals('testCode', $results['productUnitPrecision']['unit']['code']);
        $this->assertEquals($object->getQuantity(), $results['quantity']);
    }

    public function getNormalizerData(): array
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
