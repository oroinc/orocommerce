<?php

namespace Oro\Bundle\ShoppingListBundle\LineItem\Factory;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

interface LineItemByShoppingListAndProductFactoryInterface
{
    /**
     * @param ShoppingList $shoppingList
     * @param Product      $product
     *
     * @return LineItem
     */
    public function create(ShoppingList $shoppingList, Product $product): LineItem;
}
