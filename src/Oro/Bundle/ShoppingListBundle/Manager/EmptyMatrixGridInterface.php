<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Defines the contract for managing empty matrix grids for configurable products in shopping lists.
 *
 * An empty matrix grid is a line item with zero quantity that represents a configurable product
 * without any selected variants. This allows customers to see the matrix form for a configurable product
 * in their shopping list even when no variants have been added yet. Implementations of this interface
 * handle the logic for adding, checking, and managing these empty matrix line items
 * based on system configuration and the current state of the shopping list.
 */
interface EmptyMatrixGridInterface
{
    public function addEmptyMatrix(ShoppingList $shoppingList, Product $product);

    /**
     * @param LineItem[] $lineItems
     * @return bool
     */
    public function isAddEmptyMatrixAllowed(array $lineItems): bool;

    public function hasEmptyMatrix(ShoppingList $shoppingList): bool;
}
