<?php

namespace Oro\Bundle\ProductBundle\Model\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ProductBundle\Validator\QuickAddRowCollectionValidator;

class QuickAddRowInputParser
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param array $product
     * @param int $lineNumber
     * @return QuickAddRow
     */
    public function createFromFileLine($product, $lineNumber)
    {
        $sku = isset($product[0]) ? trim($product[0]) : null;
        $quantity = isset($product[1]) ? (float)$product[1] : null;
        $unit = isset($product[2]) ? trim($product[2]) : null;

        return new QuickAddRow($lineNumber, $sku, $quantity, $this->resolveUnit($sku, $unit));
    }

    /**
     * @param array $product
     * @param int $index
     * @return QuickAddRow
     */
    public function createFromRequest($product, $index)
    {
        $sku = $product[ProductDataStorage::PRODUCT_SKU_KEY];
        $quantity = $product[ProductDataStorage::PRODUCT_QUANTITY_KEY];
        $unit = isset($product[ProductDataStorage::PRODUCT_UNIT_KEY]) ?
            $product[ProductDataStorage::PRODUCT_UNIT_KEY]
            : null;

        return new QuickAddRow($index, $sku, $quantity, $this->resolveUnit($sku, $unit));
    }

    /**
     * @param array $product
     * @param int $lineNumber
     * @return QuickAddRow
     */
    public function createFromCopyPasteTextLine($product, $lineNumber)
    {
        $sku = trim($product[0]);
        $quantity = isset($product[1]) ? (float)$product[1] : null;
        $unit = isset($product[2]) ? trim($product[2]) : null;

        return new QuickAddRow($lineNumber, $sku, $quantity, $this->resolveUnit($sku, $unit));
    }

    /**
     * @param string $sku
     * @param string $unitName |null
     * @return null|string
     */
    private function resolveUnit($sku, $unitName = null)
    {
        if (!$unitName) {
            $defaultUnitName = $this->getProductRepository()->getPrimaryUnitPrecisionCode($sku);
            return $defaultUnitName ?: $unitName;
        }

        $productUnitEntity = $this->getProductUnitRepository()->findOneBy([
            'code' => $unitName
        ]);

        return $productUnitEntity ? $productUnitEntity->getCode() : $unitName;
    }

    /**
     * @return \Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository
     */
    protected function getProductUnitRepository()
    {
        return $this->registry->getRepository(ProductUnit::class);
    }

    /**
     * @return \Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository
     */
    protected function getProductRepository()
    {
        return $this->registry->getRepository(Product::class);
    }
}
