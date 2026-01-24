<?php

namespace Oro\Bundle\ShoppingListBundle\LineItem\Factory;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Defines the contract for creating shopping list line items from a product and shopping list.
 *
 * Implementations of this interface are responsible for creating properly initialized line item instances
 * with appropriate default values based on the product type and shopping list context.
 * This factory pattern allows for different line item creation strategies, such as creating empty line items
 * for configurable products (with zero quantity) or standard line items for simple products.
 * The factory ensures that line items are created with correct associations to the shopping list,
 * customer user, organization, and product unit.
 */
interface LineItemByShoppingListAndProductFactoryInterface
{
    public function create(ShoppingList $shoppingList, Product $product): LineItem;
}
