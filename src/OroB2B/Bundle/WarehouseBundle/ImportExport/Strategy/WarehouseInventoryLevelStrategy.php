<?php

namespace OroB2B\Bundle\WarehouseBundle\ImportExport\Strategy;

use Doctrine\Common\Inflector\Inflector;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class WarehouseInventoryLevelStrategy extends ConfigurableAddOrReplaceStrategy
{
    /** @var array $inventoryStatusCache */
    protected $inventoryStatusCache = [];

    /** @var array $unitRequiredCache */
    protected $unitRequiredCache = [];

    /**
     * @inheritdoc
     */
    protected function processEntity(
        $entity,
        $isFullData = false,
        $isPersistNew = false,
        $itemData = null,
        array $searchContext = array(),
        $entityIsRelation = false
    ) {
        $warehouse = $this->getWarehouse($entity);

        if (!$warehouse && $this->isWarehouseRequired($itemData)) {
            return null;
        }

        $product = $this->checkProductAndInventoryStatus($entity->getProduct());
        if (!$product) {
            return null;
        }

        $productUnitPrecision = $entity->getProductUnitPrecision();
        $productUnit = $this->checkProductUnit($product, $warehouse, $productUnitPrecision->getUnit());

        if ($productUnit) {
            $productUnitPrecision = $this->checkEntityExistence(ProductUnitPrecision::class, [
                'product' => $product,
                'unit' => $productUnit
            ]);
        } else {
            $productUnitPrecision = $this->databaseHelper->findOneByIdentity($product->getPrimaryUnitPrecision());
        }

        if (!$productUnit && empty($entity->getQuantity())) {
            $this->importExistingEntity($entity->getProduct(), $product, $itemData);

            return $product;
        }

        /** @var WarehouseInventoryLevel $existingEntity */
        $existingEntity = $this->getExistingWarehouseInventoryLevel($product, $productUnitPrecision, $warehouse);

        if (!$existingEntity) {
            $existingEntity = new WarehouseInventoryLevel();
            $existingEntity->setProductUnitPrecision($productUnitPrecision);
            $existingEntity->setWarehouse($warehouse);
        }

        $existingEntity->setQuantity($entity->getQuantity());

        // import entity fields
        if ($existingEntity) {
            $this->importExistingEntity($entity, $existingEntity, $itemData);

            $entity = $existingEntity;
        }

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
    protected function checkProductAndInventoryStatus(Product $product)
    {
        if (!empty($product->getInventoryStatus()) && !$this->validInventoryStatusConsistence($product->getSku(), $product->getInventoryStatus())) {
            $errorMessage = $this->translator->trans(
                'orob2b.warehouse.import.error.inventory_status'
            );            
            $this->strategyHelper->addValidationErrors([$errorMessage], $this->context);
            return null;
        }

        $inventoryStatus = null;
        if (!empty(trim($product->getInventoryStatus()))) {
            $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
            $inventoryStatus = $this->checkEntityExistence($inventoryStatusClassName, ['name' => $product->getInventoryStatus()]);
        }

        $existingProduct = $this->checkEntityExistence(Product::class, ['sku' => $product->getSku()]);

        if(!$existingProduct) {
            return null;
        }

        if ($inventoryStatus) {
            $existingProduct->setInventoryStatus($inventoryStatus);
        }

        return $existingProduct;
    }

    /**
     * Find a warehouse entity in the system which corresponds to the imported warehouse name.
     * If the import didn't contain the warehouse, then the main warehouse found in the system will
     * be used.
     *
     * @param WarehouseInventoryLevel $entity
     * @return null|object
     */
    protected function getWarehouse(WarehouseInventoryLevel $entity)
    {
        $warehouse = $entity->getWarehouse();
        if (!$warehouse) {
            $manager = $this->strategyHelper->getEntityManager(Warehouse::class);
            $repository = $manager->getRepository(Warehouse::class);
            return $repository->getSingularWarehouse();
        }

        return $this->checkEntityExistence(Warehouse::class, ['name' => $warehouse->getName()]);
    }

    /**
     * Verify if in the import the unit (ProductUnit) is required and if it is so and the unit
     * from the import is empty or not found then a validation error is added. Else, we search for
     * the existing ProductUnit entitty that corresponds to the import value.
     *
     * @param Product $product
     * @param Warehouse $warehouse
     * @param ProductUnit|null $productUnit
     * @return null|object
     */
    protected function checkProductUnit(Product $product, Warehouse $warehouse, ProductUnit $productUnit = null)
    {
        if (!$productUnit && $this->isUnitRequired($product, $warehouse)) {
            $errorMessage = $this->translator->trans(
                'oro.importexport.import.errors.unit_required'
            );
            $this->strategyHelper->addValidationErrors([$errorMessage], $this->context);
        }

        if (!$productUnit || empty($productUnit->getCode())) {
            return null;
        }

        return $this->checkEntityExistence(ProductUnit::class, ['code' => Inflector::singularize($productUnit->getCode())]);

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
    protected function checkEntityExistence($class, $criteria)
    {
        $existingEntity = $this->databaseHelper->findOneBy($class, $criteria);
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
    protected function validInventoryStatusConsistence($product, $inventoryStatus)
    {
        if (!array_key_exists($product, $this->inventoryStatusCache)) {
            $this->updateInventoryStatusCache($product, $inventoryStatus);
            return true;
        }

        if (!empty($inventoryStatus) && array_search($inventoryStatus, $this->inventoryStatusCache[$product]) === false) {
            return false;
        }

        $this->updateInventoryStatusCache($product, $inventoryStatus);

        return true;
    }

    /**
     * Retrieves the existing, if any, WarehouseInventoryLevel entity base on the Product,
     * ProductUnitPrecision and/or Warehouse
     *
     * @param Product $product
     * @param $productPrecisionUnit
     * @param null $warehouse
     * @return null|object
     */
    protected function getExistingWarehouseInventoryLevel(Product $product, $productPrecisionUnit, $warehouse = null)
    {
        $criteria = [
            'product' => $product,
            'productUnitPrecision' => $productPrecisionUnit
        ];

        if ($warehouse) {
            $criteria['warehouse'] = $warehouse;
        }

        $existingEntity = $this->databaseHelper->findOneBy(WarehouseInventoryLevel::class, $criteria);

        return $existingEntity;
    }

    /**
     * Add current combination of product and inventory status in the array cache
     *
     * @param $product
     * @param $inventoryStatus
     */
    protected function updateInventoryStatusCache($product, $inventoryStatus)
    {
        if (!array_key_exists($product, $this->inventoryStatusCache)) {
            $this->inventoryStatusCache[$product] = [];
        }

        $this->inventoryStatusCache[$product][] = $inventoryStatus;
    }

    /**
     * Update the cache which will be used to determine if unit is required. This cache
     * contains keys formed from product sku and warehouse name.
     *
     * @param $product
     * @param $warehouse
     */
    protected function updateUnitRequiredCache($product, $warehouse)
    {
        $key = $this->getUnitRequiredCacheKey($product, $warehouse);
        if (!array_key_exists($key , $this->unitRequiredCache)) {
            $this->unitRequiredCache[$key] = 0;
        }

        $this->unitRequiredCache[$key] = $this->unitRequiredCache[$key]++;
    }

    /**
     * Generate a key for a product and warehouse combination
     *
     * @param $product
     * @param $warehouse
     * @return string
     */
    protected function getUnitRequiredCacheKey($product, $warehouse)
    {
        return $product . '-' . $warehouse;
    }

    /**
     * Verify if the unit is required by searching in the cache for the combination of
     * product and warehouse and if the combination is found more then once then the unit
     * is required
     *
     * @param $product
     * @param $warehouse
     * @return bool
     */
    protected function isUnitRequired($product, $warehouse)
    {
        $this->updateUnitRequiredCache($product, $warehouse);

        return $this->unitRequiredCache[$this->getUnitRequiredCacheKey($product, $warehouse)] > 1;
    }

    /**
     * Check if warehouse is required by verifying that at least one Warehouse is found in the
     * system and that there is a Quantity column in the import.
     *
     * @param $importData
     * @return bool
     */
    protected function isWarehouseRequired($importData)
    {
        $manager = $this->strategyHelper->getEntityManager(Warehouse::class);
        $repository = $manager->getRepository(Warehouse::class);

        return $repository->getWarehouseCount() > 1 && array_key_exists('Quantity', $importData);
    }
}
