<?php

namespace Oro\Bundle\AccountBundle\Visibility\Cache;

use Oro\Bundle\CatalogBundle\Entity\Category;

interface CategoryCaseCacheBuilderInterface extends CacheBuilderInterface
{
    /**
     * @param Category $category
     * @return mixed
     */
    public function categoryPositionChanged(Category $category);
}
