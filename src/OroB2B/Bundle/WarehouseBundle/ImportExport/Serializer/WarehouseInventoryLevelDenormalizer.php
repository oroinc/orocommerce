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

    /**
     * WarehouseInventoryLevelDenormalizer constructor.
     */
    public function __construct(ObjectManager $manager)
    {
        $this->entityManager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
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
            $inventoryLevel->setQuantity($data['Quantity']);
        }

        if (array_key_exists('Warehouse', $data) && isset($data['Warehouse'])) {
            $warehouse = new Warehouse();
            $warehouse->setName($data['Warehouse']);
            $inventoryLevel->setWarehouse($warehouse);
        }

        if (array_key_exists('Unit', $data)) {
            $productUnit = new ProductUnit();
            $productUnit->setCode($data['Unit']);
            $productUnitPrecision->setUnit($productUnit);
        }

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
}
