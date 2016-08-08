<?php

namespace OroB2B\Bundle\WarehouseBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

class InventoryLevelDataConverter extends AbstractTableDataConverter
{
    /**
     * {@inheritDoc}
     */
    protected function getHeaderConversionRules()
    {
        return [
            'SKU' => 'product:sku',
            'Product' => 'product:defaultName',
            'Inventory Status' => 'product:inventoryStatus',
            'Warehouse' => 'warehouse:name',
            'Quantity' => 'quantity',
            'Unit' => 'productUnitPrecision:unit:code',
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getBackendHeader()
    {
        return array_values($this->getHeaderConversionRules());
    }
}
