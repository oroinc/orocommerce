<?php

namespace Oro\Bundle\CatalogBundle\Entity\EntityListener;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;

/**
 * Schedules product reindex and clears a cache for category layout data provider
 * when a Category entity is created, removed or changed.
 */
class CategoryEntityListener
{
    /** @var ProductIndexScheduler */
    private $productIndexScheduler;

    /** @var CacheProvider */
    private $categoryCache;

    /**
     * @param ProductIndexScheduler $productIndexScheduler
     * @param CacheProvider $categoryCache
     */
    public function __construct(
        ProductIndexScheduler $productIndexScheduler,
        CacheProvider $categoryCache
    ) {
        $this->productIndexScheduler = $productIndexScheduler;
        $this->categoryCache = $categoryCache;
    }

    /**
     * @param Category $category
     */
    public function preRemove(Category $category)
    {
        $this->productIndexScheduler->scheduleProductsReindex([$category]);
        $this->categoryCache->deleteAll();
    }

    /**
     * @param Category $category
     */
    public function postPersist(Category $category)
    {
        $this->productIndexScheduler->scheduleProductsReindex([$category]);
        $this->categoryCache->deleteAll();
    }

    /**
     * @param Category $category
     * @param PreUpdateEventArgs $eventArgs
     */
    public function preUpdate(Category $category, PreUpdateEventArgs $eventArgs)
    {
        if ($eventArgs->getEntityChangeSet()) {
            $this->productIndexScheduler->scheduleProductsReindex([$category]);
            $this->categoryCache->deleteAll();
        }
    }
}
