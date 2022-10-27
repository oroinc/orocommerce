<?php

namespace Oro\Bundle\ProductBundle\Helper\ProductGrouper;

/**
 * Groups array/ArrayCollection of products by SKU and Unit
 */
class ArrayProductsGrouper implements ProductsGrouperInterface
{
    /**
     * @param array $products
     * @return array
     */
    public function process($products)
    {
        $groupedArray = [];

        foreach ($products as $productRow) {
            $index = $this->createIndex($productRow);

            if ($index === null) {
                $groupedArray[] = $productRow;
                continue;
            }

            if (isset($groupedArray[$index])) {
                $this->addQuantity($groupedArray[$index], $productRow);
            } else {
                $groupedArray[$index] = $productRow;
            }
        }

        return array_values($groupedArray);
    }

    /**
     * @param array $productRow
     * @return string|null
     */
    private function createIndex($productRow)
    {
        if (!empty($productRow['productSku'])
            && !empty($productRow['productUnit'])
            && !empty($productRow['productQuantity'])
        ) {
            return sprintf('%s_%s', mb_strtoupper($productRow['productSku']), $productRow['productUnit']);
        }

        return null;
    }

    /**
     * @param array $productRowTo
     * @param array $productRowFrom
     */
    private function addQuantity(&$productRowTo, $productRowFrom)
    {
        $productRowTo['productQuantity'] += $productRowFrom['productQuantity'];
    }
}
