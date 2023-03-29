<?php

namespace Oro\Bundle\ProductBundle\Model\Builder;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;

/**
 * Creates the instance of QuickAddRow model based on the passed data.
 */
class QuickAddRowInputParser
{
    private ProductUnitsProvider $productUnitsProvider;
    private NumberFormatter $numberFormatter;

    public function __construct(
        ProductUnitsProvider $productUnitsProvider,
        NumberFormatter $numberFormatter
    ) {
        $this->productUnitsProvider = $productUnitsProvider;
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
        if ('' === $organization) {
            $organization = null;
        }

        $parsedQty = $this->numberFormatter->parseFormattedDecimal($quantity);
        if (false === $parsedQty) {
            // support non formatted quantity
            $parsedQty = (float)$quantity;
            if ((string)$parsedQty !== $quantity) {
                $parsedQty = 0.0;
            }
        }

        $unit = isset($product[2]) ? trim($product[2]) : null;

        return new QuickAddRow($lineNumber, $sku, $parsedQty, $this->resolveUnit($unit), $organization);
    }

    public function createFromArray(array $product, int $index): QuickAddRow
    {
        $sku = isset($product[QuickAddRow::SKU]) ? trim($product[QuickAddRow::SKU]) : '';
        $quantity = isset($product[QuickAddRow::QUANTITY]) ? (float)$product[QuickAddRow::QUANTITY] : 0;
        $unit = isset($product[QuickAddRow::UNIT]) ? trim($product[QuickAddRow::UNIT]) : null;
        $organization = isset($product[QuickAddRow::ORGANIZATION]) ? trim($product[QuickAddRow::ORGANIZATION]) : null;
        if ('' === $organization) {
            $organization = null;
        }

        return new QuickAddRow($index, $sku, $quantity, $this->resolveUnit($unit), $organization);
    }

    public function createFromRequest(array $product, int $index): QuickAddRow
    {
        $sku = trim($product[ProductDataStorage::PRODUCT_SKU_KEY]);
        $quantity = (float)$product[ProductDataStorage::PRODUCT_QUANTITY_KEY];
        $unit = isset($product[ProductDataStorage::PRODUCT_UNIT_KEY])
            ? trim($product[ProductDataStorage::PRODUCT_UNIT_KEY])
            : null;
        $organization = isset($product[ProductDataStorage::PRODUCT_ORGANIZATION_KEY])
            ? trim($product[ProductDataStorage::PRODUCT_ORGANIZATION_KEY])
            : null;
        if ('' === $organization) {
            $organization = null;
        }

        return new QuickAddRow($index, $sku, $quantity, $this->resolveUnit($unit), $organization);
    }

    public function createFromCopyPasteTextLine(array $product, int $lineNumber): QuickAddRow
    {
        return $this->createFromFileLine($product, $lineNumber);
    }

    private function resolveUnit(?string $unitName): ?string
    {
        if (!$unitName) {
            return null;
        }

        $resolvedUnitName = null;
        $unitLowercase = strtolower($unitName);
        $units = $this->productUnitsProvider->getAvailableProductUnits();
        foreach ($units as $translatedName => $name) {
            if (strtolower($translatedName) === $unitLowercase) {
                $resolvedUnitName = $name;
                break;
            }
            if (strtolower($name) === $unitLowercase) {
                $resolvedUnitName = $name;
            }
        }

        return $resolvedUnitName ?? $unitName;
    }
}
