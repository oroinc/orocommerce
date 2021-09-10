<?php

namespace Oro\Bundle\ProductBundle\Helper\ProductGrouper;

use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;

/**
 * Creates QuickAddRowCollection of products grouped by SKU and Unit.
 */
class QuickAddRowGrouper implements ProductsGrouperInterface
{
    /**
     * {@inheritDoc}
     * @param QuickAddRow[]|QuickAddRowCollection $products
     */
    public function process($products)
    {
        $groupedArray = [];

        foreach ($products as $productRow) {
            $index = $this->createIndex($productRow);

            if (isset($groupedArray[$index])) {
                $groupedArray[$index] = $this->addQuantity($groupedArray[$index], $productRow);
            } else {
                $groupedArray[$index] = $productRow;
            }
        }
        $collection = new QuickAddRowCollection(array_values($groupedArray));
        $collection->setAdditionalFields($products->getAdditionalFields());
        return $collection;
    }

    /**
     * @param QuickAddRow $productRow
     * @return string
     */
    private function createIndex(QuickAddRow $productRow)
    {
        return sprintf('%s_%s', mb_strtoupper($productRow->getSku()), $productRow->getUnit());
    }

    /**
     * @param QuickAddRow $productRowTo
     * @param QuickAddRow $productRowFrom
     * @return QuickAddRow
     */
    private function addQuantity(QuickAddRow $productRowTo, QuickAddRow $productRowFrom)
    {
        $mergedProductRow = new QuickAddRow(
            $productRowTo->getIndex(),
            $productRowTo->getSku(),
            $productRowTo->getQuantity() + $productRowFrom->getQuantity(),
            $productRowTo->getUnit()
        );
        if ($productRowFrom->getProduct()) {
            $mergedProductRow->setProduct($productRowFrom->getProduct());
        }
        $mergedProductRow->setValid($productRowFrom->isValid() && $productRowTo->isValid());

        $this->mergeErrors($productRowTo, $productRowFrom, $mergedProductRow);
        $this->mergeAdditionalFields($productRowTo, $productRowFrom, $mergedProductRow);

        return $mergedProductRow;
    }

    private function mergeErrors(
        QuickAddRow $productRowTo,
        QuickAddRow $productRowFrom,
        QuickAddRow $mergedProductRow
    ) {
        $errors = array_merge($productRowTo->getErrors(), $productRowFrom->getErrors());

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $mergedProductRow->addError($error['message'], $error['parameters']);
            }
        }
    }

    private function mergeAdditionalFields(
        QuickAddRow $productRowTo,
        QuickAddRow $productRowFrom,
        QuickAddRow $mergedProductRow
    ) {
        $additionalFields = array_merge($productRowTo->getAdditionalFields(), $productRowFrom->getAdditionalFields());

        if (!empty($additionalFields)) {
            foreach ($additionalFields as $additionalField) {
                $mergedProductRow->addAdditionalField($additionalField);
            }
        }
    }
}
