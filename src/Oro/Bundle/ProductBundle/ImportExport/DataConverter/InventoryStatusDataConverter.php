<?php

namespace Oro\Bundle\ProductBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

/**
 * Converts inventory status data between import/export format and internal format.
 *
 * This data converter maps column headers for inventory status import/export operations,
 * translating between user-friendly column names and internal field paths.
 */
class InventoryStatusDataConverter extends AbstractTableDataConverter
{
    #[\Override]
    protected function getHeaderConversionRules()
    {
        return [
            'SKU' => 'product:sku',
            'Product' => 'product:defaultName',
            'Inventory Status' => 'product:inventoryStatus',
        ];
    }

    #[\Override]
    protected function getBackendHeader()
    {
        return array_values($this->getHeaderConversionRules());
    }
}
