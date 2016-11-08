<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\RepositoryHolder;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountGroupCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\AccountCategoryRepository;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\AbstractResolvedCacheBuilder;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\Subtree\VisibilityChangeAccountSubtreeCacheBuilder;

class AccountCategoryResolvedCacheBuilder extends AbstractResolvedCacheBuilder
{
    /** @var VisibilityChangeAccountSubtreeCacheBuilder */
    protected $visibilityChangeAccountSubtreeCacheBuilder;

    /**
     * @var RepositoryHolder
     */
    protected $categoryVisibilityHolder;

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
        $scope = $visibilitySettings->getScope();

        $selectedVisibility = $visibilitySettings->getVisibility();
        $visibilitySettings = $this->refreshEntity($visibilitySettings);

        $insert = false;
        $delete = false;
        $update = [];
        $where = ['scope' => $scope, 'category' => $category];

        $repository = $this->getRepository();

        $hasAccountCategoryVisibilityResolved = $repository->hasEntity($where);

        if (!$hasAccountCategoryVisibilityResolved
            && $selectedVisibility !== AccountCategoryVisibility::ACCOUNT_GROUP
        ) {
            $insert = true;
        }

        if (in_array($selectedVisibility, [AccountCategoryVisibility::HIDDEN, AccountCategoryVisibility::VISIBLE])) {
            $visibility = $this->convertStaticVisibility($selectedVisibility);
            $update = [
                'visibility' => $visibility,
                'sourceCategoryVisibility' => $visibilitySettings,
                'source' => AccountCategoryVisibilityResolved::SOURCE_STATIC,
            ];
        } elseif ($selectedVisibility === AccountCategoryVisibility::CATEGORY) {
            $visibility = $this->registry
                ->getManagerForClass(CategoryVisibilityResolved::class)
                ->getRepository(CategoryVisibilityResolved::class)
                ->getFallbackToAllVisibility($category);
            $update = [
                'visibility' => $visibility,
                'sourceCategoryVisibility' => $visibilitySettings,
                'source' => AccountCategoryVisibilityResolved::SOURCE_STATIC,
            ];
        } elseif ($selectedVisibility === AccountCategoryVisibility::ACCOUNT_GROUP) {
            // Fallback to account group is default for account and should be removed if exists
            if ($hasAccountCategoryVisibilityResolved) {
                $delete = true;
            }

            if ($scope->getAccount()->getGroup()) {
                $visibility = $this->registry
                    ->getManagerForClass(AccountGroupCategoryVisibilityResolved::class)
                    ->getRepository(AccountGroupCategoryVisibilityResolved::class)
                    ->getFallbackToGroupVisibility($category, $scope);
            } else {
                $visibility = $this->registry
                    ->getManagerForClass(CategoryVisibilityResolved::class)
                    ->getRepository(CategoryVisibilityResolved::class)
                    ->getFallbackToAllVisibility($category);
            }
        } elseif ($selectedVisibility === AccountCategoryVisibility::PARENT_CATEGORY) {
            list($visibility, $source) = $this->getParentCategoryVisibilityAndSource($category, $scope);
            $update = [
                'visibility' => $visibility,
                'sourceCategoryVisibility' => $visibilitySettings,
                'source' => $source,
            ];
        } else {
            throw new \InvalidArgumentException(sprintf('Unknown visibility %s', $selectedVisibility));
        }

        $this->executeDbQuery($repository, $insert, $delete, $update, $where);

        $this->visibilityChangeAccountSubtreeCacheBuilder->resolveVisibilitySettings(
            $category,
            $scope,
            $visibility
        );
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
    public function buildCache(Scope $scope = null)
    {
        /** @var AccountCategoryRepository $resolvedRepository */
        $resolvedRepository = $this->getRepository();

        // clear table
        $resolvedRepository->clearTable();

        // resolve static values
        $resolvedRepository->insertStaticValues();

        // resolve values with fallback to category (visibility to all)
        $resolvedRepository->insertCategoryValues();

        // resolve parent category values
        $accountVisibilities = $this->indexVisibilities(
            $resolvedRepository->getParentCategoryVisibilities(),
            'visibility_id'
        );
        $accountVisibilityIds = [
            AccountCategoryVisibilityResolved::VISIBILITY_VISIBLE => [],
            AccountCategoryVisibilityResolved::VISIBILITY_HIDDEN => [],
            AccountCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG => [],
        ];
        foreach ($accountVisibilities as $visibilityId => $groupVisibility) {
            $resolvedVisibility = $this->resolveVisibility($accountVisibilities, $groupVisibility);
            $accountVisibilityIds[$resolvedVisibility][] = $visibilityId;
        }
        foreach ($accountVisibilityIds as $visibility => $ids) {
            $resolvedRepository->insertParentCategoryValues($ids, $visibility);
        }
    }

    /**
     * @param array $accountVisibilities
     * @param array $currentGroup
     * @return int
     */
    protected function resolveVisibility(array &$accountVisibilities, array $currentGroup)
    {
        // visibility already resolved
        if (array_key_exists('resolved_visibility', $currentGroup)) {
            return $currentGroup['resolved_visibility'];
        }

        $visibilityId = $currentGroup['visibility_id'];
        $parentVisibility = $currentGroup['parent_visibility'];
        $parentVisibilityId = $currentGroup['parent_visibility_id'];
        $parentGroupVisibilityResolved = $currentGroup['parent_group_resolved_visibility'];
        $parentCategoryVisibilityResolved = $currentGroup['parent_category_resolved_visibility'];

        $resolvedVisibility = null;

        // account group fallback
        if (null === $parentVisibility) {
            // use group visibility if defined, otherwise use category visibility
            $resolvedVisibility = $parentGroupVisibilityResolved !== null
                ? $parentGroupVisibilityResolved
                : $parentCategoryVisibilityResolved;
        // category fallback (visibility to all)
        } elseif ($parentVisibility === AccountCategoryVisibility::CATEGORY) {
            $resolvedVisibility = $parentCategoryVisibilityResolved;
        // parent category fallback
        } elseif ($parentVisibility === AccountCategoryVisibility::PARENT_CATEGORY) {
            $parentGroup = $accountVisibilities[$parentVisibilityId];
            $resolvedVisibility = $this->resolveVisibility($accountVisibilities, $parentGroup);
        // static visibility
        } else {
            $resolvedVisibility
                = $this->convertVisibility($parentVisibility === AccountCategoryVisibility::VISIBLE);
        }

        // config value (default)
        if (null === $resolvedVisibility) {
            $resolvedVisibility = AccountCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        }

        $accountVisibilities[$visibilityId]['resolved_visibility'] = $resolvedVisibility;

        return $resolvedVisibility;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\AccountCategoryVisibilityResolved');
    }

    /**
     * @return AccountCategoryRepository
     */
    protected function getRepository()
    {
        return $this->repositoryHolder->getRepository();
    }

    /**
     * @param Category $category
     * @param Scope $scope
     * @return array
     */
    protected function getParentCategoryVisibilityAndSource(Category $category, Scope $scope)
    {
        $parentCategory = $category->getParentCategory();
        if ($parentCategory) {
            $groupScope = null;
            if ($scope->getAccount()->getGroup()) {
                $groupScope = $this->scopeManager->find(
                    'account_category_visibility',
                    ['accountGroup' => $scope->getAccount()->getGroup()]
                );
            }
            return [
                $this->getRepository()->getFallbackToAccountVisibility($parentCategory, $scope, $groupScope),
                AccountCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY
            ];
        } else {
            return [
                AccountCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                AccountCategoryVisibilityResolved::SOURCE_STATIC
            ];
        }
    }
}
