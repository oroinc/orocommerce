<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\ProductKit\Factory;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
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

    /**
     * @param Product $product Product Kit to create a line item for.
     * @param ProductUnit|null $productUnit
     * @param float|null $quantity
     * @param ShoppingList|null $shoppingList
     *
     * @return LineItem
     */
    public function createProductKitLineItem(
        Product $product,
        ProductUnit $productUnit = null,
        float $quantity = null,
        ShoppingList $shoppingList = null
    ): LineItem {
        $lineItem = (new LineItem())
            ->setProduct($product);

        if ($shoppingList !== null) {
            $lineItem
                ->setShoppingList($shoppingList)
                ->setCustomerUser($shoppingList->getCustomerUser())
                ->setOrganization($shoppingList->getOrganization());
        }

        $productUnit = $productUnit ?? $product->getPrimaryUnitPrecision()?->getUnit();
        if ($productUnit !== null) {
            $lineItem->setUnit($productUnit);
        }

        $minimumQuantity = $lineItem->getQuantity();
        if ($productUnit !== null) {
            $productUnitPrecision = $product->getUnitPrecision($productUnit->getCode());
            if ($productUnitPrecision !== null) {
                $minimumQuantity = 1 / (10 ** $productUnitPrecision->getPrecision());
            }
        }

        $lineItem->setQuantity($quantity ?? $minimumQuantity);

        foreach ($this->productKitItemsProvider->getKitItemsAvailableForPurchase($product) as $kitItem) {
            $lineItem->addKitItemLineItem($this->kitItemLineItemFactory->createKitItemLineItem($kitItem));
        }

        return $lineItem;
    }
}
