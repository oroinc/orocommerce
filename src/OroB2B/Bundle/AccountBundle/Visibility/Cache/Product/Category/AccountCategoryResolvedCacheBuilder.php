<?php

namespace Oro\Bundle\AccountBundle\Visibility\Cache\Product\Category;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\AccountCategoryVisibilityResolved;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountCategoryRepository;
use Oro\Bundle\AccountBundle\Visibility\Cache\Product\AbstractResolvedCacheBuilder;
use Oro\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree\VisibilityChangeAccountSubtreeCacheBuilder;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class AccountCategoryResolvedCacheBuilder extends AbstractResolvedCacheBuilder
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

        $selectedVisibility = $visibilitySettings->getVisibility();
        $visibilitySettings = $this->refreshEntity($visibilitySettings);

        $insert = false;
        $delete = false;
        $update = [];
        $where = ['account' => $account, 'category' => $category];

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
                ->getManagerForClass('OroAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
                ->getRepository('OroAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
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

            $accountGroup = $account->getGroup();
            if ($accountGroup) {
                $visibility = $this->registry
                    ->getManagerForClass(
                        'OroAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved'
                    )
                    ->getRepository('OroAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
                    ->getFallbackToGroupVisibility($category, $accountGroup);
            } else {
                $visibility = $this->registry
                    ->getManagerForClass('OroAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
                    ->getRepository('OroAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
                    ->getFallbackToAllVisibility($category);
            }
        } elseif ($selectedVisibility === AccountCategoryVisibility::PARENT_CATEGORY) {
            list($visibility, $source) = $this->getParentCategoryVisibilityAndSource($category, $account);
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
            $account,
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
    public function buildCache(Website $website = null)
    {
        /** @var AccountCategoryRepository $resolvedRepository */
        $resolvedRepository = $this->registry->getManagerForClass($this->cacheClass)
            ->getRepository($this->cacheClass);

        // clear table
        $resolvedRepository->clearTable();

        // resolve static values
        $resolvedRepository->insertStaticValues($this->insertFromSelectQueryExecutor);

        // resolve values with fallback to category (visibility to all)
        $resolvedRepository->insertCategoryValues($this->insertFromSelectQueryExecutor);

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
            $resolvedRepository->insertParentCategoryValues($this->insertFromSelectQueryExecutor, $ids, $visibility);
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
            ->getManagerForClass('OroAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved');
    }

    /**
     * @return AccountCategoryRepository
     */
    protected function getRepository()
    {
        return $this->getEntityManager()
            ->getRepository('OroAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved');
    }

    /**
     * @param Category $category
     * @param Account $account
     * @return array
     */
    protected function getParentCategoryVisibilityAndSource(Category $category, Account $account)
    {
        $parentCategory = $category->getParentCategory();
        if ($parentCategory) {
            return [
                $this->getRepository()->getFallbackToAccountVisibility($parentCategory, $account),
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
