<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\ProductKit\Factory;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\ProductKit\Provider\ProductKitItemsProvider;

/**
 * Creates an instance of {@see LineItem} for the product kit for use in a shopping list.
 */
class ProductKitLineItemFactory
{
    private ProductKitItemsProvider $productKitItemsProvider;

    private ProductKitItemLineItemFactory $kitItemLineItemFactory;

    public function __construct(
        ProductKitItemsProvider $productKitItemsProvider,
        ProductKitItemLineItemFactory $kitItemLineItemFactory
    ) {
        $this->productKitItemsProvider = $productKitItemsProvider;
        $this->kitItemLineItemFactory = $kitItemLineItemFactory;
    }

    public function createProductKitLineItem(Product $product, ShoppingList $shoppingList): LineItem
    {
        $lineItem = (new LineItem())
            ->setProduct($product)
            ->setShoppingList($shoppingList)
            ->setCustomerUser($shoppingList->getCustomerUser())
            ->setOrganization($shoppingList->getOrganization());

        foreach ($this->productKitItemsProvider->getKitItemsAvailableForPurchase($product) as $kitItem) {
            $lineItem->addKitItemLineItem($this->kitItemLineItemFactory->createKitItemLineItem($kitItem));
        }

        return $lineItem;
    }
}
