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
    /** @var array $importDataCache */
    protected $importDataCache;

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
        $unit = $this->checkEntityExistence(get_class($unit), 'code', $unit->getCode());
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
        $warehouse = $this->checkEntityExistence(get_class($warehouse), 'name', $warehouse->getName());
        if (!$warehouse) {
            return null;
        }
        $entity->setWarehouse($warehouse);

        $entity = $this->afterProcessEntity($entity);
        $entity = $this->validateAndUpdateContext($entity);

        return $entity;
    }

    /**
     * Verifies if Product entity exists and if it has some properties:
     *  - the import allows for same product to be found on multiple lines and therefore we must verify if
     * these lines for a product have the same Inventory Status. If we find a product that has two types of
     * Inventory Status defined, then we have an error.
     *  - Inventory Status for this Product entity exists (the name of the status is found in the predifined statuses)
     *  - Product entity (found by SKU) exists
     *
     * @param Product $product
     * @return Product
     */
    protected function getExistingProduct(Product $product)
    {
        if (!$this->validateInventoryStatusConsistence($product->getSku(), $product->getInventoryStatus())) {
            $errorMessage = $this->translator->trans(
                'orob2b.warehouse.import.error.inventory_status'
            );            
            $this->strategyHelper->addValidationErrors([$errorMessage], $this->context);
            return null;
        }

        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
        $inventoryStatus = $this->checkEntityExistence($inventoryStatusClassName, 'name', $product->getInventoryStatus());

        $existingProduct = $this->checkEntityExistence(get_class($product), 'sku', $product->getSku());

        if(!$inventoryStatus || !$existingProduct) {
            return null;
        }

        return $existingProduct->setInventoryStatus($inventoryStatus);
    }

    /**
     * Using DatabaseHelper we search for an entity using its class name and with
     * a criteria composed of a field from this entity and its value.
     * If entity is not found then add a validation error on the context.
     *
     * @param $class
     * @param $searchFieldName
     * @param $searchFieldValue
     * @return null|object
     */
    protected function checkEntityExistence($class, $searchFieldName, $searchFieldValue)
    {
        $existingEntity = $this->databaseHelper->findOneBy($class, [$searchFieldName => $searchFieldValue]);
        if (!$existingEntity) {
            $errorMessage = $this->translator->trans(
                'oro.importexport.import.errors.not_found_entity',
                ['%entity_name%' => $class]
            );
            $this->strategyHelper->addValidationErrors([$errorMessage], $this->context);
        }

        return $existingEntity;
    }

    /**
     * The import allows for same product to be found on multiple lines and therefore we must verify if
     * these lines for a product have the same Inventory Status. If we find a product that has two types of
     * Inventory Status defined, then we have an error.
     * If the current combination of product and inventory status is not found, then it will be added
     * into an array cache.
     *
     * @param $product
     * @param $inventoryStatus
     * @return bool
     */
    protected function validateInventoryStatusConsistence($product, $inventoryStatus)
    {
        if (!array_key_exists($product, $this->importDataCache)) {
            $this->updateCache($product, $inventoryStatus);
            return true;
        }

        if (!empty($inventoryStatus) && array_search($inventoryStatus, $this->importDataCache[$product]) === false) {
            return false;
        }

        $this->updateCache($product, $inventoryStatus);

        return true;
    }

    /**
     * Add current combination of product and inventory status in the array cache
     *
     * @param $product
     * @param $inventoryStatus
     */
    protected function updateCache($product, $inventoryStatus)
    {
        if (!array_key_exists($product, $this->importDataCache)) {
            $this->importDataCache[$product] = [];
        }

        $this->importDataCache[$product][] = $inventoryStatus;
    }
}
