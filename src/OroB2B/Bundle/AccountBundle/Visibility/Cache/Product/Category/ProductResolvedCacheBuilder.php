<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree\PositionChangeCategorySubtreeCacheBuilder;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree\VisibilityChangeCategorySubtreeCacheBuilder;

class ProductResolvedCacheBuilder extends AbstractResolvedCacheBuilder implements CategoryCaseCacheBuilderInterface
{
    /** @var CacheBuilderInterface */
    protected $accountProductResolvedCacheBuilder;

    /** @var CacheBuilderInterface */
    protected $accountGroupProductResolvedCacheBuilder;

    /** @var AccountGroup[] */
    protected $intersectAccountGroupsWithFallbackToParent;

    /** @var Account[] */
    protected $accountsForUpdate;

    /** @var VisibilityChangeCategorySubtreeCacheBuilder */
    protected $visibilityChangeCategorySubtreeCacheBuilder;

    /** @var PositionChangeCategorySubtreeCacheBuilder */
    protected $positionChangeCategorySubtreeCacheBuilder;

    /**
     * @param CacheBuilderInterface $accountProductResolvedCacheBuilder
     */
    public function setAccountProductCacheBuilder(CacheBuilderInterface $accountProductResolvedCacheBuilder)
    {
        $this->accountProductResolvedCacheBuilder = $accountProductResolvedCacheBuilder;
    }

    /**
     * @param CacheBuilderInterface $accountGroupProductResolvedCacheBuilder
     */
    public function setAccountGroupProductCacheBuilder(
        CacheBuilderInterface $accountGroupProductResolvedCacheBuilder
    ) {
        $this->accountGroupProductResolvedCacheBuilder = $accountGroupProductResolvedCacheBuilder;
    }

    /**
     * @param VisibilityChangeCategorySubtreeCacheBuilder $visibilityChangeCategorySubtreeCacheBuilder
     */
    public function setVisibilityChangeCategorySubtreeCacheBuilder(
        VisibilityChangeCategorySubtreeCacheBuilder $visibilityChangeCategorySubtreeCacheBuilder
    ) {
        $this->visibilityChangeCategorySubtreeCacheBuilder = $visibilityChangeCategorySubtreeCacheBuilder;
    }

    /**
     * @param PositionChangeCategorySubtreeCacheBuilder $positionChangeCategorySubtreeCacheBuilder
     */
    public function setPositionChangeCategorySubtreeCacheBuilder(
        PositionChangeCategorySubtreeCacheBuilder $positionChangeCategorySubtreeCacheBuilder
    ) {
        $this->positionChangeCategorySubtreeCacheBuilder = $positionChangeCategorySubtreeCacheBuilder;
    }

    /**
     * @param VisibilityInterface|CategoryVisibility $visibilitySettings
     */
    public function resolveVisibilitySettings(VisibilityInterface $visibilitySettings)
    {
        $category = $visibilitySettings->getCategory();

        $this->visibilityChangeCategorySubtreeCacheBuilder->resolveVisibilitySettings($category);
    }

    /**
     * {@inheritdoc}
     */
    public function isVisibilitySettingsSupported(VisibilityInterface $visibilitySettings)
    {
        return $visibilitySettings instanceof CategoryVisibility;
    }

    /**
     * {@inheritdoc}
     */
    public function categoryPositionChanged(Category $category)
    {
        $this->positionChangeCategorySubtreeCacheBuilder->categoryPositionChanged($category);
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Website $website = null)
    {
        // TODO: Implement buildCache() method.
    }
}
