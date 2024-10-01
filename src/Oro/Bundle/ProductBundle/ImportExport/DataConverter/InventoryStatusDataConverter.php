<?php

namespace Oro\Bundle\ProductBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

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
