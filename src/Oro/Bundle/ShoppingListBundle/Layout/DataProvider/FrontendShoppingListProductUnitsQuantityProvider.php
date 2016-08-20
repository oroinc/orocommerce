<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\DataProvider\ProductShoppingListsDataProvider;

class FrontendShoppingListProductUnitsQuantityProvider
{
    /**
     * @var ProductShoppingListsDataProvider
     */
    protected $productShoppingListsDataProvider;

    /**
     * @param ProductShoppingListsDataProvider $productShoppingListsDataProvider
     */
    public function __construct(ProductShoppingListsDataProvider $productShoppingListsDataProvider)
    {
        $this->productShoppingListsDataProvider = $productShoppingListsDataProvider;
    }

    /**
     * @param Product $product
     * @return array
     */
    public function getProductUnitsQuantity(Product $product = null)
    {
        if (null === $product) {
            return null;
        }

        return $this->productShoppingListsDataProvider->getProductUnitsQuantity($product);
    }
}
