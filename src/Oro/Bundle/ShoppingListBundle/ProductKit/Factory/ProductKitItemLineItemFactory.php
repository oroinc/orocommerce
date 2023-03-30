<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\ProductKit\Factory;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Bundle\ShoppingListBundle\ProductKit\Provider\ProductKitItemProductsProvider;

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
        Product $product = null,
        ProductUnit $productUnit = null,
        float $quantity = null
    ): ProductKitItemLineItem {
        $kitItemLineItem = (new ProductKitItemLineItem())
            ->setKitItem($kitItem)
            ->setSortOrder($kitItem->getSortOrder());

        if ($product === null && !$kitItem->isOptional()) {
            $product = $this->kitItemProductsProvider->getFirstProductAvailableForPurchase($kitItem);
        }

        $kitItemLineItem->setProduct($product);

        $productUnit = $productUnit ?? $kitItem->getProductUnit();
        $kitItemLineItem->setUnit($productUnit);

        $minimumQuantity = null;
        if (!$kitItem->isOptional()) {
            $minimumQuantity = $kitItem->getMinimumQuantity();
            if ($minimumQuantity === null && $product !== null && $productUnit !== null) {
                $productUnitPrecision = $product->getUnitPrecision($productUnit->getCode());
                if ($productUnitPrecision !== null) {
                    $minimumQuantity = 1 / (10 ** $productUnitPrecision->getPrecision());
                }
            }
        }

        $kitItemLineItem->setQuantity($quantity ?? $minimumQuantity);

        return $kitItemLineItem;
    }
}
