<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category;

use Doctrine\Bundle\DoctrineBundle\Registry;

use OroB2B\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface;
use OroB2B\Bundle\AccountBundle\Visibility\Calculator\CategoryVisibilityResolver;

abstract class AbstractResolvedCacheBuilder implements CacheBuilderInterface
{
    /** @var Registry */
    protected $registry;

    /** @var CategoryVisibilityResolver */
    protected $categoryVisibilityResolver;

    /**
     * @param Registry $registry
     * @param CategoryVisibilityResolver $categoryVisibilityResolver
     */
    public function __construct(Registry $registry, CategoryVisibilityResolver $categoryVisibilityResolver)
    {
        $this->registry = $registry;
        $this->categoryVisibilityResolver = $categoryVisibilityResolver;
    }
}
