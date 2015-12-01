<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category;

use OroB2B\Bundle\AccountBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\AbstractCacheBuilder;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CacheBuilder extends AbstractCacheBuilder implements CategoryCaseCacheBuilderInterface
{
    /**
     * @var CategoryCaseCacheBuilderInterface[]
     */
    protected $builders = [];

    /**
     * @param CategoryCaseCacheBuilderInterface $cacheBuilder
     */
    public function addBuilder(CategoryCaseCacheBuilderInterface $cacheBuilder)
    {
        if (!in_array($cacheBuilder, $this->builders, true)) {
            $this->builders[] = $cacheBuilder;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function categoryPositionChanged(Category $category)
    {
        foreach ($this->builders as $builder) {
            $builder->categoryPositionChanged($category);
        }
    }
}
