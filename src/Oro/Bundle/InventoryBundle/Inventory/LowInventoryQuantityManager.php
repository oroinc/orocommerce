<?php

namespace Oro\Bundle\InventoryBundle\Inventory;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Entity\Repository\InventoryLevelRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * Class LowInventoryQuantityManager created to incapsulate Low Inventory flag logic.
 * It should be used whenever we need to check if product or products in collection have low inventory
 */
class LowInventoryQuantityManager
{
    const LOW_INVENTORY_THRESHOLD_OPTION = 'lowInventoryThreshold';
    const HIGHLIGHT_LOW_INVENTORY_OPTION = 'highlightLowInventory';

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var EntityFallbackResolver
     */
    protected $entityFallbackResolver;

    /**
     * @param EntityFallbackResolver $entityFallbackResolver
     * @param DoctrineHelper         $doctrineHelper
     */
    public function __construct(
        EntityFallbackResolver $entityFallbackResolver,
        DoctrineHelper $doctrineHelper
    ) {
        $this->entityFallbackResolver = $entityFallbackResolver;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Returns true if provided product has low inventory.
     * Second parameter can specify in what units we are going to check quantity
     *
     * @param Product          $product
     * @param ProductUnit|null $productUnit if not provided main product unit is used
     *
     * @return bool
     */
    public function isLowInventoryProduct(Product $product, ProductUnit $productUnit = null)
    {
        $highlightLowInventory = $this->entityFallbackResolver->getFallbackValue($product, 'highlightLowInventory');

        if (!$highlightLowInventory) {
            return false;
        }

        if (!$productUnit) {
            $productUnit = $product->getPrimaryUnitPrecision()->getUnit();
        }

        $lowInventoryThreshold = $this->entityFallbackResolver->getFallbackValue(
            $product,
            static::LOW_INVENTORY_THRESHOLD_OPTION
        );

        $quantity = $this->getQuantityByProductAndProductUnit($product, $productUnit);

        if ($quantity <= $lowInventoryThreshold) {
            return true;
        }

        return false;
    }

    /**
     * @param Product     $product
     * @param ProductUnit $productUnit
     *
     * @return mixed
     */
    protected function getQuantityByProductAndProductUnit(Product $product, ProductUnit $productUnit)
    {
        /** @var InventoryLevelRepository $inventoryLevelRepository */
        $inventoryLevelRepository = $this->doctrineHelper->getEntityRepositoryForClass(InventoryLevel::class);

        $inventoryLevel = $inventoryLevelRepository->getLevelByProductAndProductUnit($product, $productUnit);

        return $inventoryLevel ? $inventoryLevel->getQuantity() : 0;
    }

    /**
     * Returns low inventory flags for product collection.
     * Will be useful for all product listing (Catalog, Checkout, Shopping list)
     *
     * @param $data - [
     *      [
     *          'product' => Product Entity,
     *          'product_unit' => ProductUnit entity
     *      ]
     * ]
     *
     * @return array [
     *      'product id' => bool - has low inventory marker,
     *       ...
     *      'product id' => bool
     * ]
     */
    public function isLowInventoryCollection(array $data)
    {
        $response = [];

        $products = $this->extractProducts($data);
        $productLevelQuantities = $this->getProductLevelQuantities($products);

        foreach ($data as $item) {
            /** @var Product $product */
            $product = $item['product'];

            $highlightLowInventory = $this->entityFallbackResolver->getFallbackValue($product, 'highlightLowInventory');

            if ($highlightLowInventory) {
                /** @var ProductUnit $productUnit */
                $productUnit = $item['product_unit'];
                $code = $productUnit->getCode();

                $lowInventoryThreshold = $this->entityFallbackResolver->getFallbackValue(
                    $product,
                    'lowInventoryThreshold'
                );

                $quantity = 0;
                if (isset($productLevelQuantities[$product->getId()][$code])) {
                    $quantity = $productLevelQuantities[$product->getId()][$code];
                }

                $response[$product->getId()] = $quantity <= $lowInventoryThreshold;
            } else {
                $response[$product->getId()] = false;
            }
        }

        return $response;
    }

    /**
     * @param Product[] $products
     *
     * @return array
     */
    protected function getProductLevelQuantities(array $products)
    {
        /** @var InventoryLevelRepository $inventoryLevelRepository */
        $inventoryLevelRepository = $this->doctrineHelper->getEntityRepositoryForClass(InventoryLevel::class);
        $productLevelQuantities = $inventoryLevelRepository->getQuantityForProductCollection($products);

        return $this->formatProductLevelQuantities($productLevelQuantities);
    }

    /**
     * @param $inventoryLevelRepository
     *
     * @return array
     */
    protected function formatProductLevelQuantities($inventoryLevelRepository)
    {
        $formattedQuantities = [];

        foreach ($inventoryLevelRepository as $item) {
            $productId = $item['product_id'];
            $code = $item['code'];

            $formattedQuantities[$productId][$code] = $item['quantity'];
        }

        return $formattedQuantities;
    }

    /**
     * @param $data
     *
     * @return array
     */
    protected function extractProducts($data)
    {
        return array_column($data, 'product');
    }
}
