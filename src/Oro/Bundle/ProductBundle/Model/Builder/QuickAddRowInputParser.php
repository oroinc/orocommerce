<?php

namespace Oro\Bundle\ProductBundle\Model\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\AbstractQuery;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Creates the instance of QuickAddRow model based on the passed data.
 */
class QuickAddRowInputParser
{
    /** @var ManagerRegistry */
    private $registry;

    /** @var ProductUnitsProvider */
    private $productUnitsProvider;

    /** @var AclHelper */
    private $aclHelper;

    /**
     * @param ManagerRegistry $registry
     * @param ProductUnitsProvider $productUnitsProvider
     * @param AclHelper $aclHelper
     */
    public function __construct(
        ManagerRegistry $registry,
        ProductUnitsProvider $productUnitsProvider,
        AclHelper $aclHelper
    ) {
        $this->registry = $registry;
        $this->productUnitsProvider = $productUnitsProvider;
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param array $product
     * @param int $lineNumber
     * @return QuickAddRow
     */
    public function createFromFileLine(array $product, int $lineNumber): QuickAddRow
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
    public function createFromRequest(array $product, int $index): QuickAddRow
    {
        $sku = trim($product[ProductDataStorage::PRODUCT_SKU_KEY]);
        $quantity = (float)$product[ProductDataStorage::PRODUCT_QUANTITY_KEY];
        $unit = isset($product[ProductDataStorage::PRODUCT_UNIT_KEY])
            ? trim($product[ProductDataStorage::PRODUCT_UNIT_KEY]) : null;

        return new QuickAddRow($index, $sku, $quantity, $this->resolveUnit($sku, $unit));
    }

    /**
     * @param array $product
     * @param int $lineNumber
     * @return QuickAddRow
     */
    public function createFromCopyPasteTextLine(array $product, int $lineNumber): QuickAddRow
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
            $qb = $this->getProductRepository()->getPrimaryUnitPrecisionCodeQueryBuilder($sku);
            $defaultUnitName = $this->aclHelper->apply($qb)->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);

            return $defaultUnitName ?: $unitName;
        }

        $unit = \strtolower($unitName);

        foreach ($this->getAvailableProductUnitCodes() as $label => $code) {
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
        $units = $this->productUnitsProvider->getAvailableProductUnits();

        return \array_combine(
            \array_map('strtolower', \array_keys($units)),
            \array_map('strtolower', $units)
        );
    }

    /**
     * @return ProductRepository
     */
    private function getProductRepository(): ProductRepository
    {
        return $this->registry->getRepository(Product::class);
    }
}
