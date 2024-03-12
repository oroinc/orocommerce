<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\ProductKit\Resolver;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;

/**
 * Resolves and sets the current status for product kit using next rules
 * - Disable product kit if all products from any of the required kit items become unavailable.
 *
 * Note: ONLY required product kit items taken into account
 */
class ProductKitStatusResolver
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function resolve(Product ...$products): void
    {
        $products = array_filter($products, static fn (Product $product) => $product->getId() && $product->isKit());
        if (empty($products)) {
            return;
        }

        $data = $this->registry->getRepository(ProductKitItem::class)->getRequiredProductKitItemStatuses(
            ...array_map(static fn (Product $product) => $product->getId(), $products)
        );

        foreach ($products as $product) {
            $productData = $data[$product->getId()] ?: [];
            foreach ($productData as $datum) {
                if (!in_array(Product::STATUS_ENABLED, $datum['status'])) {
                    $product->setStatus(Product::STATUS_DISABLED);
                    continue 2;
                }
            }
        }
    }
}
