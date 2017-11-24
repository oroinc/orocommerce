<?php

namespace Oro\Bundle\ShoppingListBundle\Action;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

interface ShoppingListAndProductActionInterface
{
    /**
     * @param ShoppingList $shoppingList
     * @param Product      $product
     */
    public function execute(ShoppingList $shoppingList, Product $product);
}
