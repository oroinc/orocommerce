<?php

namespace Oro\Bundle\CatalogBundle\Entity\EntityListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
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
     */
    public function preUpdate(Category $category)
    {
        $this->productIndexScheduler->scheduleProductsReindex([$category]);
    }
}
