<?php

namespace OroB2B\Bundle\WarehouseBundle\ImportExport\Converter;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

class WarehouseInventoryLevelConverter extends AbstractTableDataConverter
{
    const PRODUCT_SKU = 'SKU';
    const PRODUCT_INVENTORY_STATUS = 'Inventory Status';
    const INVENTORY_LEVEL_QUANTITY = 'Quantity';
    const INVENTORY_LEVEL_WAREHOUSE = 'Warehouse';
    const INVENTORY_LEVEL_PRODUCT_UNIT = 'Unit';

    /**
     * @inheritdoc
     */
    protected function getHeaderConversionRules()
    {
        return [
            'sku' => 'sku',
            'product' => 'product',
            'warehouse' => 'warehouse',
            'quantity' => 'quantity',
            'unit' => 'unit',
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getBackendHeader()
    {
        return array_values($this->getHeaderConversionRules());
    }
}
