<?php

namespace Oro\Bundle\PricingBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter;

/**
 * Converts price attribute product price data between import and internal formats.
 *
 * Handles the transformation of price attribute product price data from import format
 * to internal format with proper field mapping.
 */
class PriceAttributeProductPriceDataConverter extends ConfigurableTableDataConverter
{
    #[\Override]
    protected function getHeaderConversionRules()
    {
        return [
            'Product SKU' => 'product:sku',
            'Price Attribute' => 'priceList:name',
            'Unit Code' => 'unit:code',
            'Currency' => 'currency',
            'Price' => 'value',
        ];
    }

    #[\Override]
    protected function getBackendHeader()
    {
        return [
            'product:sku',
            'priceList:name',
            'unit:code',
            'currency',
            'value',
        ];
    }
}
