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
            'Related SKUs' => 'related_skus',
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
        if (isset($exportedRecord['related_skus']) && is_array($exportedRecord['related_skus'])) {
            $exportedRecord['related_skus'] = implode(',', $exportedRecord['related_skus']);
        }

        return parent::convertToExportFormat($exportedRecord, $skipNullValues);
    }
}
