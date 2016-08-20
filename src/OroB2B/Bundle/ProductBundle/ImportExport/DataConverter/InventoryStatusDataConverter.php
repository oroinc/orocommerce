<?php

namespace Oro\Bundle\ProductBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

class InventoryStatusDataConverter extends AbstractTableDataConverter
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
