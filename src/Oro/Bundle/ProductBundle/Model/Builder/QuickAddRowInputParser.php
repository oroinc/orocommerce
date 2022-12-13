<?php

namespace Oro\Bundle\ProductBundle\Model\Builder;

use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
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
    private ManagerRegistry $registry;
    private ProductUnitsProvider $productUnitsProvider;
    private AclHelper $aclHelper;
    private NumberFormatter $numberFormatter;

    public function __construct(
        ManagerRegistry $registry,
        ProductUnitsProvider $productUnitsProvider,
        AclHelper $aclHelper,
        NumberFormatter $numberFormatter
    ) {
        $this->registry = $registry;
        $this->productUnitsProvider = $productUnitsProvider;
        $this->aclHelper = $aclHelper;
        $this->numberFormatter = $numberFormatter;
    }

    public function createFromFileLine(array $product, int $lineNumber): QuickAddRow
    {
        $sku = isset($product[0]) ? trim($product[0], "\'\" \t\n\r\0\x0B") : null;
        if (str_contains($sku, ',')) {
            [$sku, $organization] = explode(',', $sku, 2);
        }
        $quantity = isset($product[1]) ? trim($product[1]) : null;
        $organization = isset($organization) ? trim($organization) : null;
        $parsedQty = $this->numberFormatter->parseFormattedDecimal($quantity);
        if ($parsedQty === false) {
            // Support nonformatted quantity
            $parsedQty = (float)$quantity;
            if ((string)$parsedQty !== $quantity) {
                $parsedQty = 0;
            }
        }

        $unit = isset($product[2]) ? trim($product[2]) : null;

        return new QuickAddRow($lineNumber, $sku, $parsedQty, $this->resolveUnit($sku, $unit), $organization);
    }

    public function createFromArray(array $product, int $index): QuickAddRow
    {
        $sku = isset($product[QuickAddRow::SKU]) ? trim($product[QuickAddRow::SKU]) : '';
        $quantity = isset($product[QuickAddRow::QUANTITY]) ? (float)$product[QuickAddRow::QUANTITY] : 0;
        $unit = isset($product[QuickAddRow::UNIT]) ? trim($product[QuickAddRow::UNIT]) : null;

        return new QuickAddRow($index, $sku, $quantity, $this->resolveUnit($sku, $unit));
    }

    public function createFromRequest(array $product, int $index): QuickAddRow
    {
        $sku = trim($product[ProductDataStorage::PRODUCT_SKU_KEY]);
        $quantity = (float)$product[ProductDataStorage::PRODUCT_QUANTITY_KEY];
        $unit = isset($product[ProductDataStorage::PRODUCT_UNIT_KEY])
            ? trim($product[ProductDataStorage::PRODUCT_UNIT_KEY]) : null;

        return new QuickAddRow($index, $sku, $quantity, $this->resolveUnit($sku, $unit));
    }

    public function createFromCopyPasteTextLine(array $product, int $lineNumber): QuickAddRow
    {
        return $this->createFromFileLine($product, $lineNumber);
    }

    private function resolveUnit(string $sku, ?string $unitName = null): ?string
    {
        if (!$unitName) {
            $qb = $this->getProductRepository()->getPrimaryUnitPrecisionCodeQueryBuilder($sku);
            $defaultUnitName = $this->aclHelper->apply($qb)->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);

            return $defaultUnitName ?: null;
        }

        $unit = \strtolower($unitName);
        $availableUnits = $this->getAvailableProductUnitCodes();

        // Support translated unit codes
        if (\array_key_exists($unit, $availableUnits)) {
            return $availableUnits[$unit];
        }

        // Support untranslated unit codes
        if (\in_array($unit, $availableUnits, true)) {
            return $unit;
        }

        return $unitName;
    }

    private function getAvailableProductUnitCodes(): array
    {
        $units = $this->productUnitsProvider->getAvailableProductUnits();

        return \array_combine(
            \array_map('strtolower', \array_keys($units)),
            \array_map('strtolower', $units)
        );
    }

    private function getProductRepository(): ProductRepository
    {
        return $this->registry->getRepository(Product::class);
    }
}
