<?php

namespace OroB2B\Bundle\WarehouseBundle\ImportExport\Strategy;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class WarehouseInventoryLevelStrategyHelper extends AbstractWarehouseInventoryLevelStrategyHelper
{
    /**
     * {@inheritdoc}
     */
    public function process(
        WarehouseInventoryLevel $importedEntity,
        array $importData = [],
        array $newEntities = [],
        array $errors = []
    ) {
        $this->errors = $errors;

        $existingWarehouse = $this->getProcessedEntity($newEntities, 'warehouse');
        $product = $this->getProcessedEntity($newEntities, 'product');
        $productUnitPrecision = $this->getProcessedEntity($newEntities, 'productUnitPrecision');

        /** @var WarehouseInventoryLevel $existingEntity */
        $existingEntity = $this->getExistingWarehouseInventoryLevel(
            $product,
            $productUnitPrecision,
            $existingWarehouse
        );

        $existingEntity->setProductUnitPrecision($productUnitPrecision);
        $existingEntity->setWarehouse($existingWarehouse);
        $existingEntity->setQuantity($importedEntity->getQuantity());

        $newEntities['warehouseInventoryLevel'] = $existingEntity;
        if ($this->successor) {
            return $this->successor->process($importedEntity, $importData, $newEntities, $this->errors);
        }

        return $existingEntity;
    }

    /**
     * Retrieves the existing, if any, WarehouseInventoryLevel entity base on the Product,
     * ProductUnitPrecision and/or Warehouse
     *
     * @param Product $product
     * @param ProductUnitPrecision $productUnitPrecision
     * @param Warehouse $warehouse
     * @return null|WarehouseInventoryLevel
     */
    protected function getExistingWarehouseInventoryLevel(
        Product $product,
        ProductUnitPrecision $productUnitPrecision,
        Warehouse $warehouse = null
    ) {
        $criteria = [
            'product' => $product,
            'productUnitPrecision' => $productUnitPrecision
        ];

        if ($warehouse) {
            $criteria['warehouse'] = $warehouse;
        }

        $existingEntity = $this->databaseHelper->findOneBy(WarehouseInventoryLevel::class, $criteria);

        if (!$existingEntity) {
            $existingEntity = new WarehouseInventoryLevel();
        }

        return $existingEntity;
    }
}
