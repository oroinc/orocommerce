<?php

namespace OroB2B\Bundle\WarehouseBundle\ImportExport\Strategy;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class InventoryStatusesStrategyHelper extends AbstractWarehouseInventoryLevelStrategyHelper
{
    /** @var array $inventoryStatusCache */
    protected $inventoryStatusCache = [];

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

        $product = $importedEntity->getProduct();

        $existingProduct = $this->checkAndRetrieveEntity(Product::class, ['sku' => $product->getSku()]);

        if (!$existingProduct) {
            return null;
        }

        if (!empty($product->getInventoryStatus())
            && !$this->isInventoryStatusConsistent($product->getSku(), $product->getInventoryStatus())
        ) {
            $this->addError('orob2b.warehouse.import.error.inventory_status');
        }

        $inventoryStatus = null;
        if (!empty(trim($product->getInventoryStatus()))) {
            $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
            $inventoryStatus = $this->checkAndRetrieveEntity(
                $inventoryStatusClassName,
                ['name' => $product->getInventoryStatus()],
                'Inventory Status'
            );
        }

        if ($inventoryStatus) {
            $existingProduct->setInventoryStatus($inventoryStatus);
        }

        if ($importedEntity->getQuantity() === null) {
            return $existingProduct;
        }
        
        $newEntities['product'] = $existingProduct;

        if ($this->successor) {
            return $this->successor->process($importedEntity, $importData, $newEntities, $this->errors);
        }

        return $importedEntity;
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
     * {@inheritdoc}
     */
    public function clearCache($deep = false)
    {
        $this->inventoryStatusCache = [];

        parent::clearCache($deep);
    }
}
