<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category;

use Doctrine\Bundle\DoctrineBundle\Registry;

use OroB2B\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface;
use OroB2B\Bundle\AccountBundle\Visibility\Resolver\CategoryVisibilityResolverInterface;

abstract class AbstractResolvedCacheBuilder implements CacheBuilderInterface
{
    /** @var Registry */
    protected $registry;

    /** @var CategoryVisibilityResolverInterface */
    protected $categoryVisibilityResolver;

    /**
     * @param Registry $registry
     * @param CategoryVisibilityResolverInterface $categoryVisibilityResolver
     */
    public function __construct(Registry $registry, CategoryVisibilityResolverInterface $categoryVisibilityResolver)
    {
        $this->registry = $registry;
        $this->categoryVisibilityResolver = $categoryVisibilityResolver;
    }
}
