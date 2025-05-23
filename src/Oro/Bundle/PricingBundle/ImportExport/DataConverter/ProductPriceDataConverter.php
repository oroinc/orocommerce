<?php

namespace Oro\Bundle\PricingBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter;

class ProductPriceDataConverter extends ConfigurableTableDataConverter implements ContextAwareInterface
{
    /**
     * @var ContextInterface|null
     */
    protected $context;

    #[\Override]
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    #[\Override]
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        if ($this->context && empty($importedRecord['price_list_id'])) {
            $importedRecord['priceList:id'] = (int)$this->context->getOption('price_list_id');
        }

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }

    #[\Override]
    protected function getHeaderConversionRules()
    {
        return [
            'Product SKU' => 'product:sku',
            'Quantity' => 'quantity',
            'Unit Code' => 'unit:code',
            'Price' => 'value',
            'Currency' => 'currency',
        ];
    }

    #[\Override]
    protected function getBackendHeader()
    {
        return [
            'product:sku',
            'quantity',
            'unit:code',
            'value',
            'currency',
        ];
    }
}
