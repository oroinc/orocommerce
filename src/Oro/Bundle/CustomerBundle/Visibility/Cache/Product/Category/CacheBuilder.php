<?php

namespace Oro\Bundle\CustomerBundle\Visibility\Cache\Product\Category;

use Oro\Bundle\CustomerBundle\Visibility\Cache\CompositeCacheBuilder;
use Oro\Bundle\CustomerBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface;
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
