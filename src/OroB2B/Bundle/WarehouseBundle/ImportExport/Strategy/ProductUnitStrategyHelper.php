<?php

namespace OroB2B\Bundle\WarehouseBundle\ImportExport\Strategy;

use Doctrine\Common\Inflector\Inflector;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class ProductUnitStrategyHelper extends AbstractWarehouseInventoryLevelStrategyHelper
{
    /** @var array $requiredUnitCache */
    protected $requiredUnitCache = [];

    /** @var null|integer $warehouseCount  */
    protected $warehouseCount = null;

    /**
     * @inheritdoc
     */
    public function process(
        WarehouseInventoryLevel $importedEntity,
        array $importData = [],
        array $newEntities = [],
        array $errors = []
    ) {
        $this->errors = $errors;

        $existingWarehouse = isset($newEntities['warehouse']) ? $newEntities['warehouse'] : null;
        $product = isset($newEntities['product']) ? $newEntities['product'] : null;
        if (!$product || ! $existingWarehouse) {
            return null;
        }

        $productUnitPrecision = $importedEntity->getProductUnitPrecision();
        $productUnit = $productUnitPrecision->getUnit();

        if ($this->isUnitRequired($product, $existingWarehouse) && !$productUnit) {
            $this->addError('orob2b.warehouse.import.error.unit_required');

            return null;
        }

        if ($productUnit && !empty($productUnit->getCode())) {
            $productUnit = $this->checkAndRetrieveEntity(
                ProductUnit::class,
                ['code' => Inflector::singularize($productUnit->getCode())]
            );
        }

        $productUnitPrecision = $this->getPoductUnitPrecision($product, $productUnit);
        $newEntities['productUnitPrecision'] = $productUnitPrecision;

        if ($this->successor) {
            return $this->successor->process($importedEntity, $importData, $newEntities, $this->errors);
        }

        return $importedEntity;
    }

    /**
     * Return product precision unit corresponding to current product and unit or
     * extract primary product unit precision if no unit is specified
     *
     * @param Product $product
     * @param ProductUnit|null $productUnit
     * @return null|ProductUnitPrecision
     */
    protected function getPoductUnitPrecision(Product $product, ProductUnit $productUnit = null)
    {
        if ($productUnit) {
            return $this->checkAndRetrieveEntity(
                ProductUnitPrecision::class,
                [
                    'product' => $product,
                    'unit' => $productUnit
                ]
            );
        }

        return $this->databaseHelper->findOneByIdentity($product->getPrimaryUnitPrecision());
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
}
