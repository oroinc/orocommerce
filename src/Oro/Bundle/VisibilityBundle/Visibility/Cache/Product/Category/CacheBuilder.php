<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category;

use Oro\Bundle\VisibilityBundle\Visibility\Cache\CompositeCacheBuilder;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;

class CacheBuilder extends CompositeCacheBuilder implements CategoryCaseCacheBuilderInterface
{
    /**
     * {@inheritdoc}
     */
    public function categoryPositionChanged(Category $category)
    {
        foreach ($this->builders as $builder) {
            if ($builder instanceof CategoryCaseCacheBuilderInterface) {
                $builder->categoryPositionChanged($category);
            }
        }
    }
}
