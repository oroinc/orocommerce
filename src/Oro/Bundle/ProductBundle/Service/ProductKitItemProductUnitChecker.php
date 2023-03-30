<?php

namespace Oro\Bundle\ProductBundle\Service;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

/**
 * Responsible for checking if product unit is eligible for using in {@see ProductKitItem}.
 */
class ProductKitItemProductUnitChecker
{
    /**
     * Checks if product unit $unitCode can be used in {@see ProductKitItem::$productUnit}.
     *
     * @param string $unitCode
     * @param iterable<Product> $products
     *
     * @return bool
     */
    public function isProductUnitEligible(string $unitCode, iterable $products): bool
    {
        if (!$products) {
            return false;
        }

        foreach ($products as $product) {
            $productUnitPrecision = $product->getUnitPrecision($unitCode);
            if (!$productUnitPrecision) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns an array of {@see ProductUnitPrecision} with $unitCode collected from $products.
     *
     * @param string $unitCode
     * @param iterable<Product> $products
     *
     * @return ProductUnitPrecision[]
     */
    public function getEligibleProductUnitPrecisions(string $unitCode, iterable $products): array
    {
        $productUnitPrecisions = [];
        foreach ($products as $product) {
            $productUnitPrecision = $product->getUnitPrecision($unitCode);
            if ($productUnitPrecision) {
                $productUnitPrecisions[] = $productUnitPrecision;
            }
        }

        return $productUnitPrecisions;
    }

    /**
     * Returns an array of {@see Product} that do not contain a {@see ProductUnitPrecision} with $unitCode
     * within {@see Product::$unitPrecisions}.
     *
     * @param string $unitCode
     * @param iterable<Product> $products
     *
     * @return Product[]
     */
    public function getConflictingProducts(string $unitCode, iterable $products): array
    {
        $conflictingProducts = [];
        foreach ($products as $product) {
            $productUnitPrecision = $product->getUnitPrecision($unitCode);
            if (!$productUnitPrecision) {
                $conflictingProducts[] = $product;
            }
        }

        return $conflictingProducts;
    }
}
