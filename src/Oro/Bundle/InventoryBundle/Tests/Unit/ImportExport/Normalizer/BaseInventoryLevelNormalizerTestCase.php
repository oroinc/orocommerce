<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\ImportExport\Normalizer;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\ImportExport\Serializer\InventoryLevelNormalizer;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Rounding\QuantityRoundingService;

class BaseInventoryLevelNormalizerTestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var InventoryLevelNormalizer
     */
    protected $inventoryLevelNormalizer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|UnitLabelFormatterInterface */
    protected $formatter;

    /** @var  \PHPUnit\Framework\MockObject\MockObject|QuantityRoundingService */
    protected $roundingService;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FieldHelper */
    protected $fieldHelper;

    protected function setUp(): void
    {
        $this->formatter = $this->getMockBuilder(UnitLabelFormatterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->roundingService = $this->getMockBuilder(QuantityRoundingService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fieldHelper = $this->getMockBuilder(FieldHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->inventoryLevelNormalizer = new InventoryLevelNormalizer(
            $this->fieldHelper,
            $this->formatter,
            $this->roundingService
        );
    }

    /**
     * @param $quantity
     * @param $productUnitCode
     * @return InventoryLevel
     */
    public function getInventoryLevelEntity($quantity, $productUnitCode)
    {
        $object = new InventoryLevel();
        $object->setQuantity($quantity);

        $unitPrecision = new ProductUnitPrecision();
        $productUnit = new ProductUnit();
        $productUnit->setCode($productUnitCode);
        $unitPrecision->setUnit($productUnit);
        $object->setProductUnitPrecision($unitPrecision);

        return $object;
    }
}
