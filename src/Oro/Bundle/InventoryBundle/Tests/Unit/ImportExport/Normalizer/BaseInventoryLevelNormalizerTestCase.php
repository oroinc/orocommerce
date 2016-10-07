<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\ImportExport\Normalizer;

use Oro\Bundle\CurrencyBundle\Rounding\QuantityRoundingService;
use Oro\Bundle\ImportExportBundle\Field\FieldHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\ImportExport\Serializer\InventoryLevelNormalizer;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class BaseInventoryLevelNormalizerTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InventoryLevelNormalizer
     */
    protected $inventoryLevelNormalizer;

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
        $this->inventoryLevelNormalizer = new InventoryLevelNormalizer(
            $fieldHelper,
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
