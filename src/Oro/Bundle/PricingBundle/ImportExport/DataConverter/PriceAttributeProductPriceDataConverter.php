<?php

namespace Oro\Bundle\PricingBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter;

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
