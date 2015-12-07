<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree\VisibilityChangeGroupSubtreeCacheBuilder;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountGroupProductResolvedCacheBuilder extends AbstractResolvedCacheBuilder
{
    /** @var CacheBuilderInterface */
    protected $accountProductResolvedCacheBuilder;

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
     * @param CacheBuilderInterface $accountProductResolvedCacheBuilder
     */
    public function setAccountProductCacheBuilder(CacheBuilderInterface $accountProductResolvedCacheBuilder)
    {
        $this->accountProductResolvedCacheBuilder = $accountProductResolvedCacheBuilder;
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
        // TODO: Implement buildCache() method.
    }
}
