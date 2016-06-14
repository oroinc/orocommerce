<?php


namespace OroB2B\Bundle\WarehouseBundle\ImportExport\Converter;


use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

class WarehouseInventoryLevelConverter extends AbstractTableDataConverter
{

    /**
     * Get list of rules that should be user to convert,
     *
     * Example: array(
     *     'User Name' => 'userName', // key is frontend hint, value is backend hint
     *     'User Group' => array(     // convert data using regular expression
     *         self::FRONTEND_TO_BACKEND => array('User Group (\d+)', 'userGroup:$1'),
     *         self::BACKEND_TO_FRONTEND => array('userGroup:(\d+)', 'User Group $1'),
     *     )
     * )
     *
     * @return array
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
     * Get maximum backend header for current entity
     *
     * @return array
     */
    protected function getBackendHeader()
    {
        return array_values($this->getHeaderConversionRules());
    }
}
