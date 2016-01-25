<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category;

use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountGroupCategoryRepository;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\AbstractResolvedCacheBuilder;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree\VisibilityChangeGroupSubtreeCacheBuilder;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
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

        $selectedVisibility = $visibilitySettings->getVisibility();

        $insert = false;
        $delete = false;
        $where = ['accountGroup' => $accountGroup, 'category' => $category];

        $repository = $this->getRepository();

        $hasAccountGroupCategoryVisibilityResolved = $repository->hasEntity($where);

        if (!$hasAccountGroupCategoryVisibilityResolved
            && $selectedVisibility !== AccountGroupCategoryVisibility::PARENT_CATEGORY
        ) {
            $insert = true;
        }

        if (in_array(
            $selectedVisibility,
            [
                AccountGroupCategoryVisibility::HIDDEN,
                AccountGroupCategoryVisibility::VISIBLE
            ]
        )) {
            $visibility = $this->convertStaticVisibility($selectedVisibility);
            $update = [
                'visibility' => $visibility,
                'sourceCategoryVisibility' => $visibilitySettings,
                'source' => AccountGroupCategoryVisibilityResolved::SOURCE_STATIC,
            ];
        } elseif ($selectedVisibility == AccountGroupCategoryVisibility::CATEGORY) {
            $visibility = $this->registry
                ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
                ->getRepository('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
                ->getFallbackToAllVisibility($category);
            $update = [
                'visibility' => $visibility,
                'sourceCategoryVisibility' => $visibilitySettings,
                'source' => AccountGroupCategoryVisibilityResolved::SOURCE_STATIC,
            ];
        } elseif ($selectedVisibility == AccountGroupCategoryVisibility::PARENT_CATEGORY) {
            if ($category->getParentCategory()) {
                $parentCategory = $category->getParentCategory();
                $visibility = $repository->getFallbackToGroupVisibility($parentCategory, $accountGroup);
                $update = [
                    'visibility' => $visibility,
                    'sourceCategoryVisibility' => $visibilitySettings,
                    'source' => AccountGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                ];
            } else {
                throw new \LogicException('Category has not parent category');
            }
        } else {
            throw new \InvalidArgumentException(sprintf('Unknown visibility %s', $selectedVisibility));
        }
        $this->executeDbQuery($repository, $insert, $delete, $update, $where);

        $this->visibilityChangeAccountGroupSubtreeCacheBuilder
            ->resolveVisibilitySettings($category, $accountGroup, $visibility);
    }

    /**
     * @param Category $category
     * @param AccountGroup $accountGroup
     * @return int
     */
    protected function getParentCategoryVisibility(Category $category, AccountGroup $accountGroup)
    {
        $parentCategory = $category->getParentCategory();
        if ($parentCategory) {
            return $this->getRepository()->getFallbackToGroupVisibility($parentCategory, $accountGroup);
        } else {
            return AccountGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        }
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved');
    }

    /**
     * @return AccountGroupCategoryRepository
     */
    protected function getRepository()
    {
        return $this->getEntityManager()
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved');
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
        /** @var AccountGroupCategoryRepository $resolvedRepository */
        $resolvedRepository = $this->registry->getManagerForClass($this->cacheClass)
            ->getRepository($this->cacheClass);

        // clear table
        $resolvedRepository->clearTable();

        // resolve static values
        $resolvedRepository->insertStaticValues($this->insertFromSelectQueryExecutor);

        // resolve parent category values
        $groupVisibilities = $this->indexVisibilities(
            $resolvedRepository->getParentCategoryVisibilities(),
            'visibility_id'
        );
        $groupVisibilityIds = [
            AccountGroupCategoryVisibilityResolved::VISIBILITY_VISIBLE => [],
            AccountGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN => [],
            AccountGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG => [],
        ];
        foreach ($groupVisibilities as $visibilityId => $groupVisibility) {
            $resolvedVisibility = $this->resolveVisibility($groupVisibilities, $groupVisibility);
            $groupVisibilityIds[$resolvedVisibility][] = $visibilityId;
        }
        foreach ($groupVisibilityIds as $visibility => $ids) {
            $resolvedRepository->insertParentCategoryValues($this->insertFromSelectQueryExecutor, $ids, $visibility);
        }
    }

    /**
     * @param array $groupVisibilities
     * @param array $currentGroup
     * @return int
     */
    protected function resolveVisibility(array &$groupVisibilities, array $currentGroup)
    {
        // visibility already resolved
        if (array_key_exists('resolved_visibility', $currentGroup)) {
            return $currentGroup['resolved_visibility'];
        }

        $visibilityId = $currentGroup['visibility_id'];
        $parentVisibility = $currentGroup['parent_visibility'];
        $parentVisibilityId = $currentGroup['parent_visibility_id'];
        $parentCategoryVisibilityResolved = $currentGroup['parent_category_resolved_visibility'];

        $resolvedVisibility = null;

        // category fallback (visibility to all)
        if (null === $parentVisibility) {
            $resolvedVisibility = $parentCategoryVisibilityResolved;
        // parent category fallback
        } elseif ($parentVisibility === AccountGroupCategoryVisibility::PARENT_CATEGORY) {
            $parentGroup = $groupVisibilities[$parentVisibilityId];
            $resolvedVisibility = $this->resolveVisibility($groupVisibilities, $parentGroup);
        // static visibility
        } else {
            $resolvedVisibility
                = $this->convertVisibility($parentVisibility === AccountGroupCategoryVisibility::VISIBLE);
        }

        // config value (default)
        if (null === $resolvedVisibility) {
            $resolvedVisibility = AccountGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        }

        $groupVisibilities[$visibilityId]['resolved_visibility'] = $resolvedVisibility;

        return $resolvedVisibility;
    }
}
