<?php

declare(strict_types=1);

namespace Oro\Bundle\SaleBundle\ProductKit\Factory;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\ProductKit\Provider\ProductKitItemProductsProvider;
use Oro\Bundle\SaleBundle\Entity\QuoteProductKitItemLineItem;

/**
 * Creates an instance of {@see QuoteProductKitItemLineItem} for use in the product kit quote line item.
 */
class QuoteProductKitItemLineItemFactory
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
    ): QuoteProductKitItemLineItem {
        $kitItemLineItem = (new QuoteProductKitItemLineItem())
            ->setKitItem($kitItem)
            ->setSortOrder($kitItem->getSortOrder());

        if ($product === null && !$kitItem->isOptional()) {
            $product = $this->kitItemProductsProvider->getFirstAvailableProduct($kitItem);
        }

        $kitItemLineItem->setProduct($product);

        $productUnit = $productUnit ?? $kitItem->getProductUnit();
        $kitItemLineItem->setProductUnit($productUnit);

        $minimumQuantity = $kitItemLineItem->getQuantity();
        if ($kitItem->getMinimumQuantity() > 0) {
            $minimumQuantity = $kitItem->getMinimumQuantity();
        }

        $kitItemLineItem->setQuantity($quantity ?? $minimumQuantity);

        return $kitItemLineItem;
    }
}
