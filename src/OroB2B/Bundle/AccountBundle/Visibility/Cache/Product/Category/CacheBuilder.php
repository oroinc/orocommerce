<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category;

use OroB2B\Bundle\AccountBundle\Visibility\Cache\AbstractComposeCacheBuilder;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface;

class CacheBuilder extends AbstractComposeCacheBuilder
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
