<?php

namespace Oro\Bundle\InventoryBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

/**
 * Data converter for inventory level import/export operations.
 *
 * Converts between CSV headers and backend field names for inventory level data,
 * enabling proper mapping during import and export processes.
 */
class InventoryLevelDataConverter extends AbstractTableDataConverter
{
    #[\Override]
    protected function getHeaderConversionRules()
    {
        return [
            'SKU' => 'product:sku',
            'Product' => 'product:defaultName',
            'Inventory Status' => 'product:inventoryStatus',
            'Quantity' => 'quantity',
            'Unit' => 'productUnitPrecision:unit:code',
        ];
    }

    #[\Override]
    protected function getBackendHeader()
    {
        return array_values($this->getHeaderConversionRules());
    }
}
