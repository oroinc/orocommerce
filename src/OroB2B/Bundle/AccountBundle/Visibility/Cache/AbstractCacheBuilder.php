<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Visibility\Calculator\CategoryVisibilityResolverAdapterInterface;

use Symfony\Bridge\Doctrine\RegistryInterface;

abstract class AbstractCacheBuilder
{
    /** @var  RegistryInterface */
    protected $registry;

    /** @var  CategoryVisibilityResolverAdapterInterface */
    protected $categoryVisibilityResolver;

    /**
     * @param RegistryInterface $registry
     * @param CategoryVisibilityResolverAdapterInterface $categoryVisibilityResolver
     */
    public function __construct(
        RegistryInterface $registry,
        CategoryVisibilityResolverAdapterInterface $categoryVisibilityResolver
    ) {
        $this->registry = $registry;
        $this->categoryVisibilityResolver = $categoryVisibilityResolver;
    }

    /**
     * @param VisibilityInterface $productVisibility
     * @param BaseProductVisibilityResolved $productVisibilityResolved
     * @param string $selectedVisibility
     */
    protected function resolveStaticValues(
        VisibilityInterface $productVisibility,
        BaseProductVisibilityResolved $productVisibilityResolved,
        $selectedVisibility
    ) {
        $productVisibilityResolved->setSourceProductVisibility($productVisibility);
        $productVisibilityResolved->setSource(BaseProductVisibilityResolved::SOURCE_STATIC);
        $productVisibilityResolved->setCategoryId(null);
        if ($selectedVisibility == BaseProductVisibilityResolved::VISIBILITY_VISIBLE) {
            $productVisibilityResolved->setVisibility(BaseProductVisibilityResolved::VISIBILITY_VISIBLE);
        } elseif ($selectedVisibility == BaseProductVisibilityResolved::VISIBILITY_HIDDEN) {
            $productVisibilityResolved->setVisibility(BaseProductVisibilityResolved::VISIBILITY_HIDDEN);
        }
    }
}
