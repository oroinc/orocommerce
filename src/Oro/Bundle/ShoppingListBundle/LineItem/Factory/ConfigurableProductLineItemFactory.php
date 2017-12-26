<?php

namespace Oro\Bundle\ShoppingListBundle\LineItem\Factory;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ConfigurableProductLineItemFactory implements LineItemByShoppingListAndProductFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create(ShoppingList $shoppingList, Product $product): LineItem
    {
        $lineItem = new LineItem();
        $lineItem
            ->setProduct($product)
            ->setQuantity(0)
            ->setShoppingList($shoppingList)
            ->setCustomerUser($shoppingList->getCustomerUser())
            ->setOrganization($shoppingList->getOrganization())
            ->setUnit($product->getPrimaryUnitPrecision()->getUnit())
            ->setOwner($shoppingList->getOwner());

        return $lineItem;
    }
}
