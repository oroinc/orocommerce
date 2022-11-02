<?php

namespace Oro\Bundle\CatalogBundle\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;

/**
 * Receives categories that has been changed
 * and schedule reindex of products from this categories.
 */
class ProductIndexScheduler
{
    private ManagerRegistry $doctrine;
    private ProductReindexManager $productReindexManager;

    public function __construct(ManagerRegistry $doctrine, ProductReindexManager $productReindexManager)
    {
        $this->doctrine = $doctrine;
        $this->productReindexManager = $productReindexManager;
    }

    public function scheduleProductsReindex(
        array $categories,
        int $websiteId = null,
        bool $isScheduled = true,
        array $fieldGroups = null
    ): void {
        $this->productReindexManager->reindexProducts(
            $this->doctrine->getRepository(Category::class)->getProductIdsByCategories($categories),
            $websiteId,
            $isScheduled,
            $fieldGroups
        );
    }
}
