<?php

namespace Oro\Bundle\ProductBundle\Model\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;

/**
 * Creates the instance of QuickAddRow model based on the passed data.
 */
class QuickAddRowInputParser
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var ProductUnitsProvider */
    private $productUnitsProvider;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param ProductUnitsProvider $productUnitsProvider
     */
    public function setProductUnitsProvider(ProductUnitsProvider $productUnitsProvider): void
    {
        $this->productUnitsProvider = $productUnitsProvider;
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
        $unit = $product[ProductDataStorage::PRODUCT_UNIT_KEY] ?? null;

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
    private function resolveUnit(string $sku, ?string $unitName = null): ?string
    {
        if (!$unitName) {
            $defaultUnitName = $this->getProductRepository()->getPrimaryUnitPrecisionCode($sku);
            return $defaultUnitName ?: $unitName;
        }

        $unit = \strtolower($unitName);

        foreach ($this->getAvailableProductUnitCodes() as $code => $label) {
            if (\in_array($unit, [$label, $code], true)) {
                return $code;
            }
        }

        return $unitName;
    }

    /**
     * @return array
     */
    private function getAvailableProductUnitCodes(): array
    {
        if (!$this->productUnitsProvider) {
            $productUnitCodes = $this->getProductUnitRepository()->getAllUnitCodes();

            $unitsFull = [];
            foreach ($productUnitCodes as $code) {
                $unitsFull[$code] = $code;
            }

            return $unitsFull;
        }

        $units = $this->productUnitsProvider->getAvailableProductUnits();

        return \array_combine(
            \array_map('strtolower', \array_keys($units)),
            \array_map('strtolower', $units)
        );
    }

    /**
     * @return ProductUnitRepository
     */
    protected function getProductUnitRepository()
    {
        return $this->registry->getRepository(ProductUnit::class);
    }

    /**
     * @return ProductRepository
     */
    protected function getProductRepository()
    {
        return $this->registry->getRepository(Product::class);
    }
}
