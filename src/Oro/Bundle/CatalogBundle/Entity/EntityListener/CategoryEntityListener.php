<?php

namespace Oro\Bundle\CatalogBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Oro\Component\Cache\Layout\DataProviderCacheCleaner;

class CategoryEntityListener
{
    /** @var ProductIndexScheduler */
    private $productIndexScheduler;

    /** @var DataProviderCacheCleaner */
    private $categoryCacheCleaner;

    /**
     * @param ProductIndexScheduler $productIndexScheduler
     * @param DataProviderCacheCleaner $cacheCleaner
     */
    public function __construct(
        ProductIndexScheduler $productIndexScheduler,
        DataProviderCacheCleaner $cacheCleaner
    ) {
        $this->productIndexScheduler = $productIndexScheduler;
        $this->categoryCacheCleaner = $cacheCleaner;
    }

    /**
     * @param Category $category
     */
    public function preRemove(Category $category)
    {
        $this->productIndexScheduler->scheduleProductsReindex([$category]);
        $this->categoryCacheCleaner->clearCache();
    }

    /**
     * @param Category $category
     */
    public function postPersist(Category $category)
    {
        $this->productIndexScheduler->scheduleProductsReindex([$category]);
        $this->categoryCacheCleaner->clearCache();
    }

    /**
     * @param Category $category
     * @param PreUpdateEventArgs $eventArgs
     */
    public function preUpdate(Category $category, PreUpdateEventArgs $eventArgs)
    {
        if ($eventArgs->getEntityChangeSet()) {
            $this->productIndexScheduler->scheduleProductsReindex([$category]);
            $this->categoryCacheCleaner->clearCache();
        }
    }
}
