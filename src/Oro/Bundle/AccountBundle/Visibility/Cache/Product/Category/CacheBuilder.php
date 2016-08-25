<?php

namespace Oro\Bundle\AccountBundle\Visibility\Cache\Product\Category;

use Oro\Bundle\AccountBundle\Visibility\Cache\CompositeCacheBuilder;
use Oro\Bundle\AccountBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface;
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
