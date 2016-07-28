<?php

namespace OroB2B\Bundle\WarehouseBundle\ImportExport\Strategy;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;
use OroB2B\Bundle\WarehouseBundle\Model\Data\ProductUnitTransformer;

class ProductUnitStrategyHelper extends AbstractWarehouseInventoryLevelStrategyHelper
{
    /** @var array $requiredUnitCache */
    protected $requiredUnitCache = [];

    /** @var ProductUnitTransformer $productUnitTransformer */
    protected $productUnitTransformer;

    /**
     * ProductUnitStrategyHelper constructor.
     * @param DatabaseHelper $databaseHelper
     * @param TranslatorInterface $translator
     * @param ProductUnitTransformer $productUnitTransformer
     */
    public function __construct(
        DatabaseHelper $databaseHelper,
        TranslatorInterface $translator,
        ProductUnitTransformer $productUnitTransformer
    ) {
        $this->productUnitTransformer = $productUnitTransformer;
        parent::__construct($databaseHelper, $translator);
    }

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

        $productUnitPrecision = $importedEntity->getProductUnitPrecision();
        $productUnit = $productUnitPrecision->getUnit();

        if ($this->isUnitRequired($product, $existingWarehouse) && !$productUnit) {
            $this->addError('orob2b.warehouse.import.error.unit_required');

            return null;
        }

        $productUnit = $this->getProductUnit($productUnit);

        $productUnitPrecision = $this->getProductUnitPrecision($product, $productUnit);
        $newEntities['productUnitPrecision'] = $productUnitPrecision;

        if ($this->successor) {
            return $this->successor->process($importedEntity, $importData, $newEntities, $this->errors);
        }

        return $importedEntity;
    }

    /**
     * Extract the existing product unit based on its code
     *
     * @param null|ProductUnit $productUnit
     * @return null|object|ProductUnit
     */
    protected function getProductUnit(ProductUnit $productUnit = null)
    {
        if ($productUnit && !empty($productUnit->getCode())) {
            $code = $this->productUnitTransformer->transformToProductUnit($productUnit->getCode());
            $productUnit = $this->checkAndRetrieveEntity(
                ProductUnit::class,
                ['code' => $code]
            );
        }

        return $productUnit;
    }

    /**
     * Return product precision unit corresponding to current product and unit or
     * extract primary product unit precision if no unit is specified
     *
     * @param Product $product
     * @param ProductUnit|null $productUnit
     * @return null|ProductUnitPrecision
     */
    protected function getProductUnitPrecision(Product $product, ProductUnit $productUnit = null)
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

    /**
     * {@inheritdoc}
     */
    public function clearCache($deep = false)
    {
        $this->requiredUnitCache = [];

        parent::clearCache($deep);
    }
}
