<?php

namespace Oro\Bundle\ProductBundle\Helper\ProductGrouper;

use Oro\Bundle\ProductBundle\Model\ProductRow;

/**
 * Groups array/ArrayCollection of products by SKU and Unit
 */
class ProductRowsGrouper implements ProductsGrouperInterface
{
    /**
     * {@inheritDoc}
     * @param ProductRow[] $products
     * @return ProductRow[]
     */
    public function process($products)
    {
        $groupedArray = [];

        foreach ($products as $productRow) {
            $index = $this->createIndex($productRow);

            if (isset($groupedArray[$index])) {
                $this->addQuantity($groupedArray[$index], $productRow);
            } else {
                $groupedArray[$index] = $productRow;
            }
        }

        return array_values($groupedArray);
    }

    /**
     * @param ProductRow $productRow
     * @return string
     */
    private function createIndex(ProductRow $productRow)
    {
        return sprintf('%s_%s', mb_strtoupper($productRow->productSku), $productRow->productUnit);
    }

    private function addQuantity(ProductRow $productRowTo, ProductRow $productRowFrom)
    {
        $productRowTo->productQuantity += $productRowFrom->productQuantity;
    }
}
