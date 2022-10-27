<?php

namespace Oro\Bundle\CatalogBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Schedules product reindex and clears a cache for category layout data provider
 * when a Category entity is created, removed or changed.
 */
class CategoryEntityListener
{
    private ProductIndexScheduler $productIndexScheduler;
    private CacheInterface $categoryCache;

    public function __construct(
        ProductIndexScheduler $productIndexScheduler,
        CacheInterface $categoryCache
    ) {
        $this->productIndexScheduler = $productIndexScheduler;
        $this->categoryCache = $categoryCache;
    }

    public function preRemove(Category $category): void
    {
        $this->scheduleCategoryReindex($category);
    }

    public function postPersist(Category $category): void
    {
        $this->scheduleCategoryReindex($category);
    }

    public function preUpdate(Category $category, PreUpdateEventArgs $eventArgs): void
    {
        if ($eventArgs->getEntityChangeSet()) {
            $this->scheduleCategoryReindex($category);
        }
    }

    private function scheduleCategoryReindex(Category $category): void
    {
        $this->productIndexScheduler->scheduleProductsReindex([$category], null, true, ['main', 'inventory']);
        $this->categoryCache->clear();
    }
}
