<?php

namespace Oro\Bundle\ProductBundle\Helper\ProductGrouper;

use Oro\Bundle\ProductBundle\Model\ProductRow;

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
        return sprintf('%s_%s', $productRow->productSku, $productRow->productUnit);
    }

    /**
     * @param ProductRow $productRowTo
     * @param ProductRow $productRowFrom
     */
    private function addQuantity(ProductRow $productRowTo, ProductRow $productRowFrom)
    {
        $productRowTo->productQuantity += $productRowFrom->productQuantity;
    }
}
