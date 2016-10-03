<?php

namespace Oro\Bundle\WarehouseBundle\Tests\Unit\ImportExport\Normalizer;

use Oro\Bundle\ImportExportBundle\Field\FieldHelper;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use Oro\Bundle\ProductBundle\Rounding\QuantityRoundingService;
use Oro\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;
use Oro\Bundle\WarehouseBundle\ImportExport\Serializer\WarehouseInventoryLevelNormalizer;

class BaseInventoryLevelNormalizerTestCase extends \PHPUnit_Framework_TestCase
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
        $fieldHelper = $this->getMockBuilder(FieldHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->warehouseInventoryLevelNormalizer = new WarehouseInventoryLevelNormalizer(
            $fieldHelper,
            $this->formatter,
            $this->roundingService
        );
    }

    /**
     * @param $quantity
     * @param $productUnitCode
     * @return WarehouseInventoryLevel
     */
    public function getInventoryLevelEntity($quantity, $productUnitCode)
    {
        $object = new WarehouseInventoryLevel();
        $object->setQuantity($quantity);

        $unitPrecision = new ProductUnitPrecision();
        $productUnit = new ProductUnit();
        $productUnit->setCode($productUnitCode);
        $unitPrecision->setUnit($productUnit);
        $object->setProductUnitPrecision($unitPrecision);

        return $object;
    }
}
