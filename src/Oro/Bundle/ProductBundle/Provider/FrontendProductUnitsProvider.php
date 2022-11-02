<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;

/**
 * Provides a method to load product units for a specified products.
 */
class FrontendProductUnitsProvider
{
    private ManagerRegistry $doctrine;

    /** @var array [product id => [unit code, ...], ...] */
    private array $productUnits = [];

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param array $productIds
     *
     * @return array [product id => [unit code, ...], ...]
     */
    public function getUnitsForProducts(array $productIds): array
    {
        if (!$productIds) {
            throw new \InvalidArgumentException('The list of product IDs must not be empty.');
        }

        $productIdsToLoad = [];
        foreach ($productIds as $productId) {
            if (!isset($this->productUnits[$productId])) {
                $productIdsToLoad[] = $productId;
            }
        }
        if ($productIdsToLoad) {
            $loadedProductUnits = $this->getProductUnitRepository()
                ->getProductsUnitsByProductIds($productIdsToLoad);
            foreach ($productIdsToLoad as $productId) {
                $this->productUnits[$productId] = $loadedProductUnits[$productId] ?? [];
            }
        }

        $productUnits = [];
        foreach ($productIds as $productId) {
            $productUnits[$productId] = $this->productUnits[$productId];
        }

        return $productUnits;
    }

    private function getProductUnitRepository(): ProductUnitRepository
    {
        return $this->doctrine->getRepository(ProductUnit::class);
    }
}
