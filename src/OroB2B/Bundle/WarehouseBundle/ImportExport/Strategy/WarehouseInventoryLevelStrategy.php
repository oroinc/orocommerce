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

    /** @var array $requiredUnitCache */
    protected $requiredUnitCache = [];

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
        $importedWarehouse = $entity->getWarehouse();
        $existingWarehouse = $this->getWarehouse($importedWarehouse);

        if (!empty($importedWarehouse) && $existingWarehouse == null) {
            return null;
        }

        if (!$existingWarehouse && $this->isWarehouseRequired($itemData)) {
            return null;
        }

        $product = $this->getValidProductAndInventoryStatus($entity->getProduct());
        if (!$product) {
            return null;
        }

        $productUnitPrecision = $entity->getProductUnitPrecision();
        $productUnit = $this->getValidPoductUnit($product, $existingWarehouse, $productUnitPrecision->getUnit());

        if ($productUnit) {
            $productUnitPrecision = $this->checkEntityExistence(
                ProductUnitPrecision::class,
                [
                    'product' => $product,
                    'unit' => $productUnit
                ]
            );
        } else {
            $productUnitPrecision = $this->databaseHelper->findOneByIdentity($product->getPrimaryUnitPrecision());
        }

        if (!$productUnit && empty($entity->getQuantity())) {
            $this->importExistingEntity($entity->getProduct(), $product, $itemData);

            return $product;
        }

        /** @var WarehouseInventoryLevel $existingEntity */
        $existingEntity = $this->getExistingWarehouseInventoryLevel($product, $productUnitPrecision, $existingWarehouse);

        if (!$existingEntity) {
            $existingEntity = new WarehouseInventoryLevel();
        }

        $existingEntity->setProductUnitPrecision($productUnitPrecision);
        $existingEntity->setWarehouse($existingWarehouse);
        $existingEntity->setQuantity($entity->getQuantity());

        return $existingEntity;
    }

    /**
     * Verifies if Product entity exists and if it has some properties:
     *  - the import allows for the same product to be found on multiple lines and therefore we must verify if
     * all lines of a product have the same Inventory Status. If we find a product that has two types of
     * Inventory Status defined, then we have an error.
     *  - Inventory Status for this Product entity exists (the name of the status is found in the predifined statuses)
     *  - Product entity (found by SKU) exists
     *
     * @param Product $product
     * @return null|Product
     */
    protected function getValidProductAndInventoryStatus(Product $product)
    {
        if (!empty($product->getInventoryStatus())
            && !$this->isInventoryStatusConsistent($product->getSku(), $product->getInventoryStatus())
        ) {
            $errorMessage = $this->translator->trans(
                'orob2b.warehouse.import.error.inventory_status'
            );
            $this->strategyHelper->addValidationErrors([$errorMessage], $this->context);

            return null;
        }

        $inventoryStatus = null;
        if (!empty(trim($product->getInventoryStatus()))) {
            $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
            $inventoryStatus = $this->checkEntityExistence(
                $inventoryStatusClassName,
                ['name' => $product->getInventoryStatus()]
            );
        }

        $existingProduct = $this->checkEntityExistence(Product::class, ['sku' => $product->getSku()]);

        if (!$existingProduct) {
            return null;
        }

        if ($inventoryStatus) {
            $existingProduct->setInventoryStatus($inventoryStatus);
        }

        return $existingProduct;
    }

    /**
     * Find a warehouse entity in the system which corresponds to the imported warehouse name.
     * If the import doesn't contain the warehouse, then the main warehouse found in the system is
     * be used.
     *
     * @param Warehouse $warehouse
     * @return null|Warehouse
     */
    protected function getWarehouse(Warehouse $warehouse = null)
    {
        if (!$warehouse) {
            $manager = $this->strategyHelper->getEntityManager(Warehouse::class);
            $repository = $manager->getRepository(Warehouse::class);
            $warehouse = $repository->getSingularWarehouse();
        }

        if (!$warehouse) {
            $errorMessage = $this->translator->trans(
                'oro.importexport.import.errors.warehouse_inexistent'
            );
            $this->strategyHelper->addValidationErrors([$errorMessage], $this->context);

            return null;
        }

        return $this->checkEntityExistence(Warehouse::class, ['name' => $warehouse->getName()]);
    }

    /**
     * Verify if in the import the unit (ProductUnit) is required and if so and the unit
     * from the import is empty or not found then a validation error is added. Else, we search for
     * the existing ProductUnit entity that corresponds to the import value.
     *
     * @param Product $product
     * @param Warehouse $warehouse
     * @param ProductUnit|null $productUnit
     * @return null|ProductUnit
     */
    protected function getValidPoductUnit(Product $product, Warehouse $warehouse, ProductUnit $productUnit = null)
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

        return $this->checkEntityExistence(
            ProductUnit::class,
            ['code' => Inflector::singularize($productUnit->getCode())]
        );
    }

    /**
     * Using DatabaseHelper we search for an entity using its class name and
     * a criteria composed of a field from this entity and its value.
     * If entity is not found then add a validation error on the context.
     *
     * @param string $class
     * @param array $criteria
     * @return null|object
     */
    protected function checkEntityExistence($class, array $criteria = [])
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
     * @param string $productSku
     * @param string $inventoryStatusName
     * @return bool
     */
    protected function isInventoryStatusConsistent($productSku, $inventoryStatusName)
    {
        if (!array_key_exists($productSku, $this->inventoryStatusCache)) {
            $this->updateInventoryStatusCache($productSku, $inventoryStatusName);

            return true;
        }

        if (!empty($inventoryStatusName)
            && array_search($inventoryStatusName, $this->inventoryStatusCache[$productSku]) === false
        ) {
            return false;
        }

        $this->updateInventoryStatusCache($productSku, $inventoryStatusName);

        return true;
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

        return $existingEntity;
    }

    /**
     * Add current combination of product and inventory status in the array cache
     *
     * @param string $productSku
     * @param string $inventoryStatusName
     */
    protected function updateInventoryStatusCache($productSku, $inventoryStatusName)
    {
        if (!array_key_exists($productSku, $this->inventoryStatusCache)) {
            $this->inventoryStatusCache[$productSku] = [];
        }

        $this->inventoryStatusCache[$productSku][] = $inventoryStatusName;
    }

    /**
     * Update the cache which will be used to determine if unit is required. This cache
     * contains keys formed from product sku and warehouse name.
     *
     * @param string $productSku
     * @param string $warehouseName
     */
    protected function updateUnitRequiredCache($productSku, $warehouseName)
    {
        $key = $this->getUnitRequiredCacheKey($productSku, $warehouseName);
        if (!array_key_exists($key, $this->requiredUnitCache)) {
            $this->requiredUnitCache[$key] = 0;
        }

        $this->requiredUnitCache[$key]++;
    }

    /**
     * Generate a key for a product and warehouse combination
     *
     * @param string $productSku
     * @param string $warehouseName
     * @return string
     */
    protected function getUnitRequiredCacheKey($productSku, $warehouseName)
    {
        return $productSku . '-' . $warehouseName;
    }

    /**
     * Verify if the unit is required by searching in the cache for the combination of
     * product and warehouse and if the combination is found more then once then the unit
     * is required
     *
     * @param Product $product
     * @param Warehouse $warehouse
     * @return bool
     */
    protected function isUnitRequired(Product $product, Warehouse $warehouse)
    {
        $this->updateUnitRequiredCache($product->getSku(), $warehouse->getName());

        return $this->requiredUnitCache[$this->getUnitRequiredCacheKey($product->getSku(), $warehouse->getName())] > 1;
    }

    /**
     * Check if warehouse is required by verifying that at least one Warehouse is found in the
     * system and that there is a Quantity column in the import.
     *
     * @param array $importData
     * @return bool
     */
    protected function isWarehouseRequired(array $importData)
    {
        $manager = $this->strategyHelper->getEntityManager(Warehouse::class);
        $repository = $manager->getRepository(Warehouse::class);

        return $repository->getWarehouseCount() > 1 && array_key_exists('Quantity', $importData);
    }
}
