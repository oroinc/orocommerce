<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache;

use OroB2B\Bundle\CatalogBundle\Entity\Category;

interface CategoryCaseCacheBuilderInterface extends CacheBuilderInterface
{
    /**
     * @param Category $category
     * @return mixed
     */
    public function categoryPositionChanged(Category $category);
}
