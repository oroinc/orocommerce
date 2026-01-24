<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CompositeCacheBuilder;

/**
 * Composite cache builder for product visibility that handles category-specific events.
 *
 * This cache builder extends the composite pattern to support category position change events,
 * delegating to child builders that implement {@see CategoryCaseCacheBuilderInterface} to rebuild
 * product visibility cache when category positions change in the catalog tree.
 */
class CacheBuilder extends CompositeCacheBuilder implements CategoryCaseCacheBuilderInterface
{
    #[\Override]
    public function categoryPositionChanged(Category $category)
    {
        foreach ($this->builders as $builder) {
            if ($builder instanceof CategoryCaseCacheBuilderInterface) {
                $builder->categoryPositionChanged($category);
            }
        }
    }
}
