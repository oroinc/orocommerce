<?php

namespace OroB2B\Bundle\WarehouseBundle\ImportExport\Serializer;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;
use OroB2B\Bundle\WarehouseBundle\ImportExport\DataConverter\InventoryLevelDataConverter as Converter;

class WarehouseInventoryLevelDenormalizer implements DenormalizerInterface
{
    const PRODUCT = 'product';
    const PRODUCT_INVENTORY_STATUS = 'inventoryStatus';
    const INVENTORY_LEVEL_QUANTITY = 'Quantity';
    const INVENTORY_LEVEL_WAREHOUSE = 'Warehouse';
    const INVENTORY_LEVEL_PRODUCT_UNIT = 'Unit';

    /** @var ObjectManager $entityManager */
    protected $entityManager;

    /**
     * @param ObjectManager $manager
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
        if (!is_array($data) || !isset($data[self::PRODUCT])) {
            return null;
        }

        $productData = $data['product'];

        $productEntity = new Product();
        $productEntity->setSku($productData['sku']);

        $inventoryLevel = new WarehouseInventoryLevel();
        $productUnitPrecision = new ProductUnitPrecision();

        $productUnitPrecision->setProduct($productEntity);

        if (array_key_exists('inventoryStatus', $productData)) {
            $productEntity->setInventoryStatus($productData['inventoryStatus']);
        }

        if (array_key_exists('quantity', $data)) {
            $inventoryLevel->setQuantity($data['quantity']);
        }

        if (isset($data['warehouse'])) {
            $warehouse = new Warehouse();
            $warehouse->setName($data['warehouse']['name']);
            $inventoryLevel->setWarehouse($warehouse);
        }

        if (array_key_exists('productUnitPrecision', $data)) {
            $productUnitPrecisionData = $data['productUnitPrecision'];

            $productUnit = new ProductUnit();
            $productUnit->setCode($data['productUnitPrecision']['unit']['code']);
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
        return !empty($data) && isset($data[self::PRODUCT]) && $type === WarehouseInventoryLevel::class;
    }
}
