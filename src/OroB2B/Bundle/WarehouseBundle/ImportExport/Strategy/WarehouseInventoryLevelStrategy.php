<?php

namespace OroB2B\Bundle\WarehouseBundle\ImportExport\Strategy;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class WarehouseInventoryLevelStrategy extends ConfigurableAddOrReplaceStrategy
{

    /**
     * Process entity according to current strategy
     * Return either updated entity, or null if entity must not be used
     *
     * @param WarehouseInventoryLevel $entity
     * @return mixed|null
     */
    public function process($entity)
    {
        $this->assertEnvironment($entity);

        $entity = $this->beforeProcessEntity($entity);

        $productUnitPrecision = $entity->getProductUnitPrecision();
        $unit = $productUnitPrecision->getUnit();
        $unit = $this->checkEntityExistence(get_class($unit), 'code', $unit->getCode(), 'Product Unit not found');
        if (!$unit) {
            return null;
        }
        $productUnitPrecision->setUnit($unit);

        $product = $this->getExistingProduct($entity->getProduct());
        if (!$product) {
            return null;
        }
        $productUnitPrecision->setProduct($product);
        $entity->setProductUnitPrecision($productUnitPrecision);

        $warehouse = $entity->getWarehouse();
        $warehouse = $this->checkEntityExistence(get_class($warehouse), 'name', $warehouse->getName(), 'Warehouse not found');
        if (!$warehouse) {
            return null;
        }
        $entity->setWarehouse($warehouse);

        $entity = $this->afterProcessEntity($entity);
        $entity = $this->validateAndUpdateContext($entity);

        return $entity;
    }

    protected function getExistingProduct(Product $product)
    {
        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
        $inventoryStatus = $this->checkEntityExistence($inventoryStatusClassName, 'name', $product->getInventoryStatus(), 'Product Inventory Status not found');

        $existingProduct = $this->checkEntityExistence(get_class($product), 'sku', $product->getSku(), 'Product with given SKU not found');

        if(!$inventoryStatus || !$existingProduct) {
            return null;
        }

        return $existingProduct->setInventoryStatus($inventoryStatus);
    }

    protected function checkEntityExistence($class, $searchFieldName, $searchFieldValue, $errorMessage)
    {
        $existingEntity = $this->databaseHelper->findOneBy($class, [$searchFieldName => $searchFieldValue]);
        if (!$existingEntity) {
            $this->strategyHelper->addValidationErrors([$errorMessage], $this->context);
        }

        return $existingEntity;
    }
}
