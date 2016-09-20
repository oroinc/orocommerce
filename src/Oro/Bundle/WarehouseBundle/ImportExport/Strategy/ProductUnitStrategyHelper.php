<?php

namespace Oro\Bundle\WarehouseBundle\ImportExport\Strategy;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;
use Oro\Bundle\WarehouseBundle\Model\Data\ProductUnitTransformer;

class ProductUnitStrategyHelper extends AbstractInventoryLevelStrategyHelper
{
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

        $product = $this->getProcessedEntity($newEntities, 'product');

        $productUnitPrecision = $importedEntity->getProductUnitPrecision();
        $productUnit = $productUnitPrecision->getUnit();
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
     * {@inheritdoc}
     */
    public function clearCache($deep = false)
    {
        $this->requiredUnitCache = [];

        parent::clearCache($deep);
    }
}
