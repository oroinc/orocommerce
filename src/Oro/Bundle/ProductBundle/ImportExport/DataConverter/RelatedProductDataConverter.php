<?php

namespace Oro\Bundle\ProductBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

/**
 * Converts exportedRecord to the format expected by its destination.
 * Converts importedRecord to the format which is used to deserialize data from the array.
 */
class RelatedProductDataConverter extends AbstractTableDataConverter
{
    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules(): array
    {
        return [
            'SKU' => 'sku',
            'Related SKUs' => 'relatedItem',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader(): array
    {
        return array_values($this->getHeaderConversionRules());
    }

    /**
     * {@inheritdoc}
     */
    public function convertToExportFormat(array $exportedRecord, $skipNullValues = true): array
    {
        if (isset($exportedRecord['relatedItem']) && is_array($exportedRecord['relatedItem'])) {
            $exportedRecord['relatedItem'] = implode(',', $exportedRecord['relatedItem']);
        }

        return parent::convertToExportFormat($exportedRecord, $skipNullValues);
    }
}
