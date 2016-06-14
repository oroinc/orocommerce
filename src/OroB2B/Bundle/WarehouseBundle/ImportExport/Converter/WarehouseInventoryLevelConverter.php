<?php


namespace OroB2B\Bundle\WarehouseBundle\ImportExport\Converter;


use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

class WarehouseInventoryLevelConverter extends AbstractTableDataConverter
{
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
