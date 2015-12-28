<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\Repository\AccountGroupCategoryVisibilityRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupCategoryVisibilityResolved;
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
        $categoryVisibilities = $this->indexVisibilities(
            $categoryRepository->getCategoriesWithResolvedVisibilities(),
            'category_id'
        );
        $groupVisibilities = $this->indexVisibilities(
            $repository->getParentCategoryVisibilities(),
            'visibility_id'
        );
        $groupIds = [
            AccountGroupCategoryVisibilityResolved::VISIBILITY_VISIBLE => [],
            AccountGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN => [],
        ];
        foreach ($groupVisibilities as $visibilityId => $groupVisibility) {
            $resolvedVisibility = $this->resolveVisibility($groupVisibilities, $categoryVisibilities, $groupVisibility);
            $groupIds[$resolvedVisibility][] = $visibilityId;
        }
        foreach ($groupIds as $visibility => $ids) {
            $resolvedRepository->insertParentCategoryValues($this->insertFromSelectQueryExecutor, $ids, $visibility);
        }
    }

    /**
     * @param array $groupVisibilities
     * @param array $categoryVisibilities
     * @param array $currentGroup
     * @return int
     */
    protected function resolveVisibility(array &$groupVisibilities, array $categoryVisibilities, array $currentGroup)
    {
        // visibility already resolved
        if (array_key_exists('resolved_visibility', $currentGroup)) {
            return $currentGroup['resolved_visibility'];
        }

        $visibilityId = $currentGroup['visibility_id'];
        $parentCategoryId = $currentGroup['parent_category_id'];
        $parentVisibility = $currentGroup['parent_visibility'];
        $parentVisibilityId = $currentGroup['parent_visibility_id'];

        $resolvedVisibility = null;

        // category fallback (visibility to all)
        if (null === $parentVisibility) {
            $resolvedVisibility = $categoryVisibilities[$parentCategoryId]['resolved_visibility'];
        // parent category fallback
        } elseif ($parentVisibility === AccountGroupCategoryVisibility::PARENT_CATEGORY) {
            $parentGroup = $groupVisibilities[$parentVisibilityId];
            $resolvedVisibility = $this->resolveVisibility($groupVisibilities, $categoryVisibilities, $parentGroup);
        // static visibility
        } else {
            $resolvedVisibility
                = $this->convertVisibility($parentVisibility === AccountGroupCategoryVisibility::VISIBLE);
        }

        // config value (default)
        if (null === $resolvedVisibility) {
            $resolvedVisibility = $this->getVisibilityFromConfig();
        }

        $groupVisibilities[$visibilityId]['resolved_visibility'] = $resolvedVisibility;

        return $resolvedVisibility;
    }
}
