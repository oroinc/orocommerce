<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\ProductKit\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\ProductKit\Checker\ProductKitItemAvailabilityChecker;

/**
 * Provides kit items available for purchase for the specified {@see Product} kit.
 */
class ProductKitItemsProvider
{
    private ProductKitItemAvailabilityChecker $productKitItemAvailabilityChecker;

    public function __construct(ProductKitItemAvailabilityChecker $productKitItemAvailabilityChecker)
    {
        $this->productKitItemAvailabilityChecker = $productKitItemAvailabilityChecker;
    }

    /**
     * @param Product $productKit
     *
     * @return array<ProductKitItem>
     */
    public function getAvailableKitItems(Product $productKit): array
    {
        $kitItems = [];
        foreach ($productKit->getKitItems() as $kitItem) {
            $isKitItemAvailable = $this->productKitItemAvailabilityChecker->isAvailable($kitItem);
            if ($isKitItemAvailable) {
                $kitItems[] = $kitItem;
            }
        }

        return $kitItems;
    }
}
