<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache;

use Oro\Bundle\CatalogBundle\Entity\Category;

interface CategoryCaseCacheBuilderInterface extends CacheBuilderInterface
{
    /**
     * @param Category $category
     * @return mixed
     */
    public function categoryPositionChanged(Category $category);
}
