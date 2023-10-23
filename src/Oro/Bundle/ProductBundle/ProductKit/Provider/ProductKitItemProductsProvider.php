<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\ProductKit\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\ProductKit\Checker\ProductKitItemProductAvailabilityChecker;

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

    public function getAvailableProducts(ProductKitItem $productKitItem): array
    {
        $products = [];
        foreach ($productKitItem->getKitItemProducts() as $kitItemProduct) {
            if ($this->kitItemProductAvailabilityChecker->isAvailable($kitItemProduct)) {
                $products[] = $kitItemProduct->getProduct();
            }
        }

        return $products;
    }

    public function getFirstAvailableProduct(ProductKitItem $productKitItem): ?Product
    {
        foreach ($productKitItem->getKitItemProducts() as $kitItemProduct) {
            if ($this->kitItemProductAvailabilityChecker->isAvailable($kitItemProduct)) {
                return $kitItemProduct->getProduct();
            }
        }

        return null;
    }
}
