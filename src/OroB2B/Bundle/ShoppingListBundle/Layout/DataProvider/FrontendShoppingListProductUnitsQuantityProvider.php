<?php

namespace OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\DataProvider\ProductShoppingListsDataProvider;

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
