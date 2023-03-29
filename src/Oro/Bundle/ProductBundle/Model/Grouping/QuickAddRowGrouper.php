<?php

namespace Oro\Bundle\ProductBundle\Model\Grouping;

use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;

/**
 * The service to group duplicated rows in QuickAddRowCollection.
 */
class QuickAddRowGrouper implements QuickAddRowGrouperInterface
{
    /**
     * {@inheritDoc}
     */
    public function groupProducts(QuickAddRowCollection $collection): void
    {
        $groupedRows = [];
        /** @var QuickAddRow $productRow */
        foreach ($collection as $productRow) {
            $key = $this->createRowKey($productRow);
            if (isset($groupedRows[$key])) {
                $this->joinRows($groupedRows[$key], $productRow);
            } else {
                $groupedRows[$key] = $productRow;
            }
        }

        $collection->clear();
        foreach ($groupedRows as $row) {
            $collection->add($row);
        }
    }

    private function createRowKey(QuickAddRow $productRow): string
    {
        $organization = $productRow->getOrganization();
        if ($organization) {
            $organization = mb_strtolower($organization);
        }

        return sprintf('%s_%s_%s', mb_strtoupper($productRow->getSku()), $productRow->getUnit(), $organization);
    }

    private function joinRows(QuickAddRow $row, QuickAddRow $anotherRow): void
    {
        $row->setQuantity($row->getQuantity() + $anotherRow->getQuantity());
        foreach ($anotherRow->getErrors() as $error) {
            $row->addError($error['message'], $error['parameters'], $error['propertyPath']);
        }
        foreach ($anotherRow->getAdditionalFields() as $fieldName => $field) {
            if (null === $row->getAdditionalField($fieldName)) {
                $row->addAdditionalField($field);
            }
        }
    }
}
