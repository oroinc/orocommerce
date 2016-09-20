<?php

namespace Oro\Bundle\WarehouseBundle\ImportExport\Strategy;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class InventoryLevelStrategyHelper extends AbstractInventoryLevelStrategyHelper
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

        $product = $this->getProcessedEntity($newEntities, 'product');
        $productUnitPrecision = $this->getProcessedEntity($newEntities, 'productUnitPrecision');

        /** @var WarehouseInventoryLevel $existingEntity */
        $existingEntity = $this->getExistingWarehouseInventoryLevel(
            $product,
            $productUnitPrecision
        );

        $existingEntity->setProductUnitPrecision($productUnitPrecision);
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
     * @return null|WarehouseInventoryLevel
     */
    protected function getExistingWarehouseInventoryLevel(
        Product $product,
        ProductUnitPrecision $productUnitPrecision
    ) {
        $criteria = [
            'product' => $product,
            'productUnitPrecision' => $productUnitPrecision
        ];

        $existingEntity = $this->databaseHelper->findOneBy(WarehouseInventoryLevel::class, $criteria);

        if (!$existingEntity) {
            $existingEntity = new WarehouseInventoryLevel();
        }

        return $existingEntity;
    }
}
