<?php

namespace Oro\Bundle\ProductBundle\Helper\ProductGrouper;

/**
 * Factory for groupers that can group products by SKU and Unit.
 */
class ProductsGrouperFactory
{
    const ARRAY_PRODUCTS = 'array';
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
