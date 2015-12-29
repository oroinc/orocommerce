<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\Repository\AccountCategoryVisibilityRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountCategoryRepository;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\AbstractResolvedCacheBuilder;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree\VisibilityChangeAccountSubtreeCacheBuilder;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountProductResolvedCacheBuilder extends AbstractResolvedCacheBuilder
{
    /** @var VisibilityChangeAccountSubtreeCacheBuilder */
    protected $visibilityChangeAccountSubtreeCacheBuilder;

    /**
     * @param VisibilityChangeAccountSubtreeCacheBuilder $visibilityChangeAccountSubtreeCacheBuilder
     */
    public function setVisibilityChangeAccountSubtreeCacheBuilder(
        VisibilityChangeAccountSubtreeCacheBuilder $visibilityChangeAccountSubtreeCacheBuilder
    ) {
        $this->visibilityChangeAccountSubtreeCacheBuilder = $visibilityChangeAccountSubtreeCacheBuilder;
    }

    /**
     * @param VisibilityInterface|AccountCategoryVisibility $visibilitySettings
     */
    public function resolveVisibilitySettings(VisibilityInterface $visibilitySettings)
    {
        $category = $visibilitySettings->getCategory();
        $account = $visibilitySettings->getAccount();

        $this->visibilityChangeAccountSubtreeCacheBuilder->resolveVisibilitySettings($category, $account);
    }

    /**
     * {@inheritdoc}
     */
    public function isVisibilitySettingsSupported(VisibilityInterface $visibilitySettings)
    {
        return $visibilitySettings instanceof AccountCategoryVisibility;
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Website $website = null)
    {
        /** @var AccountCategoryVisibilityRepository $repository */
        $repository = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:Visibility\AccountCategoryVisibility')
            ->getRepository('OroB2BAccountBundle:Visibility\AccountCategoryVisibility');
        /** @var AccountCategoryRepository $resolvedRepository */
        $resolvedRepository = $this->registry->getManagerForClass($this->cacheClass)
            ->getRepository($this->cacheClass);

        // clear table
        $resolvedRepository->clearTable();

        // resolve static values
        $resolvedRepository->insertStaticValues($this->insertFromSelectQueryExecutor);

        // resolve values with fallback to category (visibility to all)
        $resolvedRepository->insertCategoryValues($this->insertFromSelectQueryExecutor);

        // TODO: Implement in scope of BB-1650
    }
}
