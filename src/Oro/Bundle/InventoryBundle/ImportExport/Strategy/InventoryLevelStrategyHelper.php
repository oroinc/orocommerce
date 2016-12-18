<?php

namespace Oro\Bundle\InventoryBundle\ImportExport\Strategy;

use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class InventoryLevelStrategyHelper extends AbstractInventoryLevelStrategyHelper
{
    /**
     * {@inheritdoc}
     */
    public function process(
        InventoryLevel $importedEntity,
        array $importData = [],
        array $newEntities = [],
        array $errors = []
    ) {
        $this->errors = $errors;

        $product = $this->getProcessedEntity($newEntities, 'product');
        if (!$product) {
            // The product should exist, otherwise an error should have been added in InventoryStatusesStrategyHelper,
            // but if in any case the product is null at this step, this import entry should not be processed
            return;
        }

        $productUnitPrecision = $this->getProcessedEntity($newEntities, 'productUnitPrecision');
        if (!$productUnitPrecision) {
            // Same as the product comment above
            return;
        }

        /** @var InventoryLevel $existingEntity */
        $existingEntity = $this->getExistingInventoryLevel(
            $product,
            $productUnitPrecision
        );

        $existingEntity->setProductUnitPrecision($productUnitPrecision);
        $existingEntity->setQuantity($importedEntity->getQuantity());

        $newEntities['inventoryLevel'] = $existingEntity;
        if ($this->successor) {
            return $this->successor->process($importedEntity, $importData, $newEntities, $this->errors);
        }

        return $existingEntity;
    }

    /**
     * Retrieves the existing, if any, inventoryLevel entity base on the Product, ProductUnitPrecision
     *
     * @param Product $product
     * @param ProductUnitPrecision $productUnitPrecision
     * @return null|InventoryLevel
     */
    protected function getExistingInventoryLevel(Product $product, ProductUnitPrecision $productUnitPrecision)
    {
        $criteria = [
            'product' => $product,
            'productUnitPrecision' => $productUnitPrecision
        ];

        $existingEntity = $this->databaseHelper->findOneBy(InventoryLevel::class, $criteria);

        if (!$existingEntity) {
            $existingEntity = new InventoryLevel();
        }

        return $existingEntity;
    }
}
