<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeServiceInterface;

/**
 * Provides a method to load product units for a specified products.
 */
class FrontendProductUnitsProvider
{
    private ManagerRegistry $doctrine;

    private SingleUnitModeServiceInterface $singleUnitModeService;

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

    public function getUnitsForProduct(Product $product): array
    {
        $units = $product->getSellUnitsPrecision();
        if ($this->singleUnitModeService->isSingleUnitMode() && \count($units) > 1) {
            $primaryPrecision = $product->getPrimaryUnitPrecision();
            if ($primaryPrecision && $primaryPrecision->isSell()) {
                return [$primaryPrecision->getUnit()->getCode() => $primaryPrecision->getPrecision()];
            }

            return \array_slice($units, 0, 1, true);
        }

        return $units;
    }

    private function getProductUnitRepository(): ProductUnitRepository
    {
        return $this->doctrine->getRepository(ProductUnit::class);
    }

    public function setSingleUnitModeService(SingleUnitModeServiceInterface $singleUnitModeService): void
    {
        $this->singleUnitModeService = $singleUnitModeService;
    }
}
