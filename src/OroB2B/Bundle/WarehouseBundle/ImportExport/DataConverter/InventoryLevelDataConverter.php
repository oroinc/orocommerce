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
            'Inventory status' => 'product:inventoryStatus',
            'Warehouse' => 'warehouse:name',
            'Unit' => 'productUnitPrecision:unit:code',
            'Quantity' => 'quantity',
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
