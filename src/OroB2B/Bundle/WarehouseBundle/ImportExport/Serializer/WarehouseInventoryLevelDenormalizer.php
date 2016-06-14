<?php

namespace OroB2B\Bundle\WarehouseBundle\ImportExport\Serializer;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class WarehouseInventoryLevelDenormalizer implements DenormalizerInterface
{
    /** @var ObjectManager $entityManager */
    protected $entityManager;

    /** @var integer $warehouseCount */
    protected $warehouseCount;

    /**
     * WarehouseInventoryLevelDenormalizer constructor.
     */
    public function __construct(ObjectManager $manager)
    {
        $this->entityManager = $manager;
        $this->warehouseCount = $this->getWarehouseCount();
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        //TODO: cache for verifyng multiple product lines?
        if (!is_array($data) || !isset($data['SKU'])) {
            return null;
        }
        
        $product = new Product();
        $product->setSku($data['SKU']);

        $inventoryLevel = new WarehouseInventoryLevel();
        $productUnitPrecision = new ProductUnitPrecision();

        $productUnitPrecision->setProduct($product);

        if (array_key_exists('Inventory Status', $data)) {
            $product->setInventoryStatus($data['Inventory Status']);
        }

        if (array_key_exists('Quantity', $data)) {
            //TODO: - we have quantity but no unit is specified, so we assume primary unit of a product in the row:
            $inventoryLevel->setQuantity($data['Quantity']);
        }

        //TODO: if count(warehouse) = 0 => error
        if ($this->isWarehouseRequired($data)) {
            if (!(array_key_exists('Warehouse', $data) && isset($data['Warehouse']))) {
                return null;
            }

            $warehouse = new Warehouse();
            $warehouse->setName($data['Warehouse']);
            $inventoryLevel->setWarehouse($warehouse);
        }

        if ($this->isUnitRequired() && !(array_key_exists('Unit', $data) && isset($data['Unit']))) {
            return null;
        }

        $productUnit = new ProductUnit();
        $productUnit->setCode($data['Unit']);
        $productUnitPrecision->setUnit($productUnit);

        $inventoryLevel->setProductUnitPrecision($productUnitPrecision);

        return $inventoryLevel;
    }

    /**
     * @inheritdoc
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = array())
    {
        return !empty($data) && isset($data['SKU']) &&
            $type === 'OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel';
    }

    protected function getWarehouseCount()
    {
        return $this->entityManager->getRepository('OroB2BWarehouseBundle:Warehouse')->warehouseCount();
    }

    protected function isWarehouseRequired($importData)
    {
        return $this->warehouseCount > 1 && array_key_exists('Quantity', $importData);
    }

    protected function isUnitRequired()
    {
        //TODO
        return true;
    }
}
