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
     * @param DoctrineHelper $doctrineHelper
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
        if (!$productUnit) {
            $productUnit = $product->getPrimaryUnitPrecision()->getUnit();
        }

        /** @var InventoryLevelRepository $inventoryLevelRepository */
        $inventoryLevelRepository = $this->doctrineHelper->getEntityRepositoryForClass(InventoryLevel::class);
        /** @var InventoryLevel $inventoryLevel */
        $inventoryLevel = $inventoryLevelRepository->getLevelByProductAndProductUnit($product, $productUnit);
        $productQuantity = $inventoryLevel->getQuantity();

        $lowInventoryThreshold = $this->entityFallbackResolver->getFallbackValue($product, 'lowInventoryThreshold');

        if ($productQuantity <= $lowInventoryThreshold) {
            return true;
        }

        return false;
    }

    /**
     * Returns low inventory flags for product collection.
     * Will be useful for all product listing (Catalog, Checkout, Shopping list)
     *
     * @param $products - [
     *      [
     *          'productId' => value,
     *          'productUnit' => productUnit entity
     *      ]
     * ]
     *
     * @return array [
     *      'productId' => bool - has low inventory marker,
     *       ...
     *      'productId' => bool
     * ]
     */
    public function isLowInventoryCollection($products)
    {
        $response = [];

        foreach ($products as $product) {
            if (isset($product['productId'])) {
                $productId = $product['productId'];
                $productUnit = null;
                if (isset($product['productUnit'])) {
                    $productUnit = $product['productUnit'];
                }

                //TODO: Two lines below is just a STUB which will be replaced in scope of BB-12178
                /** @var Product $product */
                $productEntity = $this->doctrineHelper->getEntity(Product::class, $productId);
                $hasLowInventoryMarker = $this->isLowInventoryProduct($productEntity, $productUnit);

                $response[$productId] = $hasLowInventoryMarker;
            }
        }

        return $response;
    }
}
