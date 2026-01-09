<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache;

use Oro\Bundle\CatalogBundle\Entity\Category;

/**
 * Defines the contract for visibility cache builders that handle category-specific events.
 *
 * Implementations of this interface must provide methods to rebuild visibility cache when category positions change
 * in the catalog tree, in addition to the standard cache building operations defined in {@see CacheBuilderInterface}.
 */
interface CategoryCaseCacheBuilderInterface extends CacheBuilderInterface
{
    /**
     * @param Category $category
     * @return mixed
     */
    public function categoryPositionChanged(Category $category);
}
