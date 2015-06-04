<?php

namespace OroB2B\Bundle\PricingBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

class ProductPriceDataConverter extends AbstractTableDataConverter implements ContextAwareInterface
{
    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        return [
            'product_sku' => 'product:sku',
            'unit_code' => 'unit:code',
            'quantity' => 'quantity',
            'price' => 'value',
            'currency' => 'currency'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        if (empty($importedRecord['price_list_id'])) {
            $importedRecord['priceList:id'] = (int)$this->context->getOption('price_list_id');
        }

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        return array_values($this->getHeaderConversionRules());
    }
}
