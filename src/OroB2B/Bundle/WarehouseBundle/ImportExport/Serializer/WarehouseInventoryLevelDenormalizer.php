<?php

namespace OroB2B\Bundle\WarehouseBundle\ImportExport\Serializer;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;
use OroB2B\Bundle\WarehouseBundle\ImportExport\Converter\WarehouseInventoryLevelConverter as Converter;

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
        if (!is_array($data) || !isset($data[Converter::PRODUCT_SKU])) {
            return null;
        }
        
        $product = new Product();
        $product->setSku($data[Converter::PRODUCT_SKU]);

        $inventoryLevel = new WarehouseInventoryLevel();
        $productUnitPrecision = new ProductUnitPrecision();

        $productUnitPrecision->setProduct($product);

        if (array_key_exists(Converter::PRODUCT_INVENTORY_STATUS, $data)) {
            $product->setInventoryStatus($data[Converter::PRODUCT_INVENTORY_STATUS]);
        }

        if (array_key_exists(Converter::INVENTORY_LEVEL_QUANTITY, $data)) {
            $inventoryLevel->setQuantity($data[Converter::INVENTORY_LEVEL_QUANTITY]);
        }

        if (array_key_exists(Converter::INVENTORY_LEVEL_WAREHOUSE, $data) && isset($data[Converter::INVENTORY_LEVEL_WAREHOUSE])) {
            $warehouse = new Warehouse();
            $warehouse->setName($data[Converter::INVENTORY_LEVEL_WAREHOUSE]);
            $inventoryLevel->setWarehouse($warehouse);
        }

        if (array_key_exists(Converter::INVENTORY_LEVEL_PRODUCT_UNIT, $data)) {
            $productUnit = new ProductUnit();
            $productUnit->setCode($data[Converter::INVENTORY_LEVEL_PRODUCT_UNIT]);
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
        return !empty($data) && isset($data[Converter::PRODUCT_SKU]) &&
            $type === WarehouseInventoryLevel::class;
    }
}
