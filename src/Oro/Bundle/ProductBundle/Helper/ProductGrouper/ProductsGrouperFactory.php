<?php

namespace Oro\Bundle\ProductBundle\Helper\ProductGrouper;

class ProductsGrouperFactory
{
    const ARRAY_PRODUCTS = 'array';
    const PRODUCT_ROW = 'ProductRow';
    const QUICK_ADD_ROW = 'QuickAddRow';

    /**
     * @param string $type
     * @return ProductsGrouperInterface
     * @throws UnknownGrouperException
     */
    public function createProductsGrouper($type)
    {
        switch ($type) {
            case self::ARRAY_PRODUCTS:
                return new ArrayProductsGrouper();

            case self::PRODUCT_ROW:
                return new ProductRowsGrouper();

            case self::QUICK_ADD_ROW:
                return new QuickAddRowGrouper();
        }

        throw new UnknownGrouperException(
            sprintf(
                'There is no Products Grouper for "%s".',
                $type
            )
        );
    }
}
