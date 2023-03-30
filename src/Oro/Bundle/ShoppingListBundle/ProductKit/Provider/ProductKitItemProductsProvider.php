<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\ProductKit\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ShoppingListBundle\ProductKit\Checker\ProductKitItemProductAvailabilityChecker;

/**
 * Provides products available for purchase for the specified {@see ProductKitItem}.
 */
class ProductKitItemProductsProvider
{
    private ProductKitItemProductAvailabilityChecker $kitItemProductAvailabilityChecker;

    public function __construct(ProductKitItemProductAvailabilityChecker $kitItemProductAvailabilityChecker)
    {
        $this->kitItemProductAvailabilityChecker = $kitItemProductAvailabilityChecker;
    }

    public function getProductsAvailableForPurchase(ProductKitItem $productKitItem): array
    {
        $products = [];
        foreach ($productKitItem->getKitItemProducts() as $kitItemProduct) {
            if ($this->kitItemProductAvailabilityChecker->isAvailableForPurchase($kitItemProduct)) {
                $products[] = $kitItemProduct->getProduct();
            }
        }

        return $products;
    }

    public function getFirstProductAvailableForPurchase(ProductKitItem $productKitItem): ?Product
    {
        foreach ($productKitItem->getKitItemProducts() as $kitItemProduct) {
            if ($this->kitItemProductAvailabilityChecker->isAvailableForPurchase($kitItemProduct)) {
                return $kitItemProduct->getProduct();
            }
        }

        return null;
    }
}
