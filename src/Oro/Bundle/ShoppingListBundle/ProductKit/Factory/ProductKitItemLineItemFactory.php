<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\ProductKit\Factory;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\ProductKit\Provider\ProductKitItemProductsProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;

/**
 * Creates an instance of {@see ProductKitItemLineItem} for use in the product kit shopping list line item.
 */
class ProductKitItemLineItemFactory
{
    private ProductKitItemProductsProvider $kitItemProductsProvider;

    public function __construct(ProductKitItemProductsProvider $kitItemProductsProvider)
    {
        $this->kitItemProductsProvider = $kitItemProductsProvider;
    }

    public function createKitItemLineItem(
        ProductKitItem $kitItem,
        ?Product $product = null,
        ?ProductUnit $productUnit = null,
        ?float $quantity = null
    ): ProductKitItemLineItem {
        $kitItemLineItem = (new ProductKitItemLineItem())
            ->setKitItem($kitItem)
            ->setSortOrder($kitItem->getSortOrder());

        if ($product === null && !$kitItem->isOptional()) {
            $product = $this->kitItemProductsProvider->getFirstAvailableProduct($kitItem);
        }

        $kitItemLineItem->setProduct($product);

        $productUnit = $productUnit ?? $kitItem->getProductUnit();
        $kitItemLineItem->setUnit($productUnit);

        $minimumQuantity = $kitItemLineItem->getQuantity();
        if ($kitItem->getMinimumQuantity() > 0) {
            $minimumQuantity = $kitItem->getMinimumQuantity();
        }

        $kitItemLineItem->setQuantity($quantity ?? $minimumQuantity);

        return $kitItemLineItem;
    }
}
