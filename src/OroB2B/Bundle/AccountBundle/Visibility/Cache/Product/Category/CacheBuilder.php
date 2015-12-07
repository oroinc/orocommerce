<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category;

use OroB2B\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\AbstractCacheBuilder;

class CacheBuilder extends AbstractCacheBuilder implements CacheBuilderInterface
{
    /**
     * @var CacheBuilderInterface[]
     */
    protected $builders = [];

    /**
     * @param CacheBuilderInterface $cacheBuilder
     */
    public function addBuilder(CacheBuilderInterface $cacheBuilder)
    {
        if (!in_array($cacheBuilder, $this->builders, true)) {
            $this->builders[] = $cacheBuilder;
        }
    }
}
