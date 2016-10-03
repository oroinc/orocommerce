<?php

namespace Oro\Bundle\CatalogBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Event\AfterProductRecalculateVisibility;
use Oro\Bundle\CatalogBundle\Event\ProductsChangeRelationEvent;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;

class CategoryEntityListener
{
    /** @var ProductIndexScheduler */
    private $productIndexScheduler;

    /**
     * @param ProductIndexScheduler $productIndexScheduler
     */
    public function __construct(ProductIndexScheduler $productIndexScheduler)
    {
        $this->productIndexScheduler = $productIndexScheduler;
    }

    /**
     * @param Category $category
     */
    public function preRemove(Category $category)
    {
        $this->productIndexScheduler->scheduleProductsReindex([$category]);
    }

    /**
     * @param Category $category
     */
    public function postPersist(Category $category)
    {
        $this->productIndexScheduler->scheduleProductsReindex([$category]);
    }

    /**
     * @param Category $category
     * @param PreUpdateEventArgs $eventArgs
     */
    public function preUpdate(Category $category, PreUpdateEventArgs $eventArgs)
    {
        if ($eventArgs->getEntityChangeSet()) {
            $this->productIndexScheduler->scheduleProductsReindex([$category]);
        }
    }

    /**
     * @param AfterProductRecalculateVisibility $event
     */
    public function afterProductRecalculateVisibility(AfterProductRecalculateVisibility $event)
    {
        $product = $event->getProduct();
        $this->productIndexScheduler->triggerReindexationRequestEvent([$product->getId()]);
    }
}
