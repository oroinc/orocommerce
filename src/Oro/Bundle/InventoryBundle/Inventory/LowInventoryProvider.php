<?php

namespace Oro\Bundle\InventoryBundle\Inventory;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * Encapsulate Low Inventory flag logic.
 * It should be used whenever we need to check if product or products in collection have low inventory
 */
class LowInventoryProvider
{
    public const LOW_INVENTORY_THRESHOLD_OPTION = 'lowInventoryThreshold';
    public const HIGHLIGHT_LOW_INVENTORY_OPTION = 'highlightLowInventory';

    protected EntityFallbackResolver $entityFallbackResolver;
    protected ManagerRegistry $doctrine;

    public function __construct(
        EntityFallbackResolver $entityFallbackResolver,
        ManagerRegistry $doctrine
    ) {
        $this->entityFallbackResolver = $entityFallbackResolver;
        $this->doctrine = $doctrine;
    }

    /**
     * Returns true if provided product has low inventory.
     * Second parameter can specify in what units we are going to check quantity,
     * if it is not provided the main product unit is used.
     */
    public function isLowInventoryProduct(Product $product, ProductUnit $productUnit = null): bool
    {
        if ($productUnit === null) {
            $productUnit = $this->getDefaultProductUnit($product);
        }

        if ($productUnit instanceof ProductUnit && $this->enabledHighlightLowInventory($product)) {
            $lowInventoryThreshold = $this->entityFallbackResolver->getFallbackValue(
                $product,
                static::LOW_INVENTORY_THRESHOLD_OPTION
            );

            $quantity = $this->getQuantityByProductAndProductUnit($product, $productUnit);

            if ($quantity <= $lowInventoryThreshold) {
                return true;
            }
        }

        return false;
    }

    protected function getQuantityByProductAndProductUnit(Product $product, ProductUnit $productUnit): mixed
    {
        $inventoryLevel = $this->doctrine->getRepository(InventoryLevel::class)
            ->getLevelByProductAndProductUnit($product, $productUnit);

        return $inventoryLevel ? $inventoryLevel->getQuantity() : 0;
    }

    /**
     * Returns low inventory flags for product collection.
     * Will be useful for all product listing (Catalog, Checkout, Shopping list).
     *
     * @param array $data products collection with optional ProductUnit's
     * [
     *     [
     *         'product' => Product entity,
     *         'product_unit' => ProductUnit entity (optional),
     *         'highlight_low_inventory' => bool (optional)
     *         'low_inventory_threshold' => int (optional),
     *     ],
     *     ...
     * ]
     *
     * @return array [product id => is low inventory, ...]
     */
    public function isLowInventoryCollection(array $data): array
    {
        $response = [];

        $products = $this->extractProducts($data);
        $productLevelQuantities = $this->getProductLevelQuantities($products);

        foreach ($data as $item) {
            /** @var Product $product */
            $product = $item['product'];
            $productUnit = $item['product_unit'] ?? $this->getDefaultProductUnit($product);

            $hasLowInventory = false;
            if ($productUnit instanceof ProductUnit) {
                $highlightLowInventory = $item['highlight_low_inventory']
                    ?? $this->enabledHighlightLowInventory($product);
                if ($highlightLowInventory) {
                    $code = $productUnit->getCode();
                    $lowInventoryThreshold = $item['low_inventory_threshold']
                        ?? $this->getLowInventoryThreshold($product);
                    $quantity = $productLevelQuantities[$product->getId()][$code] ?? 0;
                    $hasLowInventory = ($quantity <= $lowInventoryThreshold);
                }
            }
            $response[$product->getId()] = $hasLowInventory;
        }

        return $response;
    }

    protected function enabledHighlightLowInventory(Product $product): mixed
    {
        return $this->entityFallbackResolver->getFallbackValue(
            $product,
            static::HIGHLIGHT_LOW_INVENTORY_OPTION
        );
    }

    protected function getLowInventoryThreshold(Product $product): mixed
    {
        return $this->entityFallbackResolver->getFallbackValue(
            $product,
            static::LOW_INVENTORY_THRESHOLD_OPTION
        );
    }

    /**
     * @param Product[] $products
     *
     * @return array [product id => [product unit => quantity, ...], ...]
     */
    protected function getProductLevelQuantities(array $products): array
    {
        return $this->formatProductLevelQuantities(
            $this->doctrine->getRepository(InventoryLevel::class)
                ->getQuantityForProductCollection($products)
        );
    }

    protected function formatProductLevelQuantities(array $productLevelQuantities): array
    {
        $formattedQuantities = [];
        foreach ($productLevelQuantities as $item) {
            $formattedQuantities[$item['product_id']][$item['code']] = $item['quantity'];
        }

        return $formattedQuantities;
    }

    protected function extractProducts(array $data): array
    {
        return array_column($data, 'product');
    }

    protected function getDefaultProductUnit(Product $product): ?ProductUnit
    {
        return $product->getPrimaryUnitPrecision()?->getUnit();
    }
}
