<?php

namespace Oro\Bundle\InventoryBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

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
