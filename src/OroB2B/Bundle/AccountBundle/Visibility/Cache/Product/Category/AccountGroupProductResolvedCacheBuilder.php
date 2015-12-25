<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\Repository\AccountGroupCategoryVisibilityRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountGroupCategoryRepository;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\CategoryRepository;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\AbstractResolvedCacheBuilder;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree\VisibilityChangeGroupSubtreeCacheBuilder;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountGroupProductResolvedCacheBuilder extends AbstractResolvedCacheBuilder
{
    /** @var VisibilityChangeGroupSubtreeCacheBuilder */
    protected $visibilityChangeAccountGroupSubtreeCacheBuilder;

    /**
     * @param VisibilityChangeGroupSubtreeCacheBuilder $visibilityChangeAccountGroupSubtreeCacheBuilder
     */
    public function setVisibilityChangeAccountSubtreeCacheBuilder(
        VisibilityChangeGroupSubtreeCacheBuilder $visibilityChangeAccountGroupSubtreeCacheBuilder
    ) {
        $this->visibilityChangeAccountGroupSubtreeCacheBuilder = $visibilityChangeAccountGroupSubtreeCacheBuilder;
    }

    /**
     * @param VisibilityInterface|AccountGroupCategoryVisibility $visibilitySettings
     */
    public function resolveVisibilitySettings(VisibilityInterface $visibilitySettings)
    {
        $category = $visibilitySettings->getCategory();
        $accountGroup = $visibilitySettings->getAccountGroup();

        $this->visibilityChangeAccountGroupSubtreeCacheBuilder->resolveVisibilitySettings($category, $accountGroup);
    }

    /**
     * {@inheritdoc}
     */
    public function isVisibilitySettingsSupported(VisibilityInterface $visibilitySettings)
    {
        return $visibilitySettings instanceof AccountGroupCategoryVisibility;
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Website $website = null)
    {
        /** @var CategoryRepository $repository */
        $categoryRepository = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved');
        /** @var AccountGroupCategoryVisibilityRepository $repository */
        $repository = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility')
            ->getRepository('OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility');
        /** @var AccountGroupCategoryRepository $resolvedRepository */
        $resolvedRepository = $this->registry->getManagerForClass($this->cacheClass)
            ->getRepository($this->cacheClass);

        // clear table
        $resolvedRepository->clearTable();

        // resolve static values
        $resolvedRepository->insertStaticValues($this->insertFromSelectQueryExecutor);

        // resolve parent category values
        $categoryVisibilities = $this->indexVisibilities($categoryRepository->getCategoriesWithResolvedVisibilities());
        $groupVisibilities = $repository->getParentCategoryVisibilities();
        var_dump($groupVisibilities);

    }

}
