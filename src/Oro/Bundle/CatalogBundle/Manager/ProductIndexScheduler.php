<?php

namespace Oro\Bundle\CatalogBundle\Manager;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;

/**
 * Receives categories that has been changed and schedule
 * reindex of products from this categories
 */
class ProductIndexScheduler
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ProductReindexManager */
    private $productReindexManager;

    public function __construct(DoctrineHelper $doctrineHelper, ProductReindexManager $productReindexManager)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->productReindexManager = $productReindexManager;
    }

    /**
     * @param Category[] $categories
     * @param int|null $websiteId
     * @param bool $isScheduled
     */
    public function scheduleProductsReindex(array $categories, $websiteId = null, $isScheduled = true)
    {
        /** @var CategoryRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(Category::class);
        $productIds = $repository->getProductIdsByCategories($categories);
        $this->productReindexManager->reindexProducts($productIds, $websiteId, $isScheduled);
    }
}
