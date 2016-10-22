<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\Subtree;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\AccountGroupCategoryRepository;

class VisibilityChangeGroupSubtreeCacheBuilder extends AbstractRelatedEntitiesAwareSubtreeCacheBuilder
{
    /** @var Category */
    protected $category;

    /** @var AccountGroup */
    protected $accountGroup;

    /**
     * @param Category $category
     * @param Scope $scope
     * @param int $visibility
     */
    public function resolveVisibilitySettings(Category $category, Scope $scope, $visibility)
    {
        $childCategoryIds = $this->getChildCategoryIdsForUpdate($category, $scope);
        $this->updateGroupCategoryVisibility($childCategoryIds, $visibility, $scope);

        $categoryIds = $this->getCategoryIdsForUpdate($category, $childCategoryIds);
        $productScopes = $this->scopeManager
            ->findRelatedScopeIds(AccountGroupProductVisibility::VISIBILITY_TYPE, $scope);
        $this->updateProductVisibilityByCategory($categoryIds, $visibility, $productScopes);

        $this->category = $category;
        $this->accountGroup = $scope->getAccountGroup();

        $this->updateProductVisibilitiesForCategoryRelatedEntities($category, $visibility);

        $this->clearChangedEntities();
    }

    /**
     * @param array $categoryIds
     * @param int $visibility
     * @param Scope $scope
     */
    protected function updateGroupCategoryVisibility(array $categoryIds, $visibility, Scope $scope)
    {
        if (!$categoryIds) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->update('OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved', 'agcvr')
            ->set('agcvr.visibility', $visibility)
            ->where($qb->expr()->eq('agcvr.scope', ':scope'))
            ->andWhere($qb->expr()->in('IDENTITY(agcvr.category)', ':categoryIds'))
            ->setParameters(['scope' => $scope, 'categoryIds' => $categoryIds]);

        $qb->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function updateAccountGroupsFirstLevel(Category $category, $visibility)
    {
        $accountGroupId = $this->accountGroup->getId();

        // if really first level - use account group
        if ($category->getId() === $this->category->getId()) {
            return [$accountGroupId];
        // if not - check if category visibility has fallback to original category
        } else {
            $parentCategory = $category->getParentCategory();
            if ($parentCategory && !empty($this->accountGroupIdsWithChangedVisibility[$parentCategory->getId()])) {
                $visibility = $this->registry
                    ->getManagerForClass('OroVisibilityBundle:Visibility\AccountGroupCategoryVisibility')
                    ->getRepository('OroVisibilityBundle:Visibility\AccountGroupCategoryVisibility')
                    ->getAccountGroupCategoryVisibility($this->accountGroup, $category);
                if ($visibility === AccountGroupCategoryVisibility::PARENT_CATEGORY) {
                    return [$accountGroupId];
                }
            }
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function updateAccountsFirstLevel(Category $category, $visibility)
    {
        // if not first level - check if category has fallback to original category
        if ($category->getId() != $this->category->getId()
            && empty($this->accountGroupIdsWithChangedVisibility[$category->getId()])
        ) {
            return [];
        }

        $accountIdsForUpdate = $this->getAccountIdsWithFallbackToCurrentGroup($category, $this->accountGroup);
        $this->updateAccountsProductVisibility($category, $accountIdsForUpdate, $visibility);

        return $accountIdsForUpdate;
    }

    /**
     * @param Category $category
     * @param AccountGroup $accountGroup
     * @return array
     */
    protected function getAccountIdsWithFallbackToCurrentGroup(Category $category, AccountGroup $accountGroup)
    {
        /** @var Account[] $groupAccounts */
        $groupAccounts = $accountGroup->getAccounts()->toArray();
        if (empty($groupAccounts)) {
            return [];
        }

        $groupAccountIds = [];
        foreach ($groupAccounts as $account) {
            $groupAccountIds[] = $account->getId();
        }
        $scopes = $this->scopeManager->findRelatedScopeIds(
            'account_category_visibility',
            ['account' => $groupAccountIds]
        );
        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroCustomerBundle:Account')
            ->createQueryBuilder();

        /** @var QueryBuilder $subQb */
        $subQb = $this->registry
            ->getManagerForClass('OroCustomerBundle:Account')
            ->createQueryBuilder();

        $subQb->select('1')
            ->from('OroVisibilityBundle:Visibility\AccountCategoryVisibility', 'accountCategoryVisibility')
            ->join('accountCategoryVisibility.scope', 'scope')
            ->where($qb->expr()->andX(
                $qb->expr()->in('accountCategoryVisibility.scope', ':scopes'),
                $qb->expr()->eq('accountCategoryVisibility.category', ':category'),
                $qb->expr()->eq('scope.account', 'account')
            ));
        $qb->select('account.id')
            ->from('OroCustomerBundle:Account', 'account')
            ->where($qb->expr()->not($qb->expr()->exists($subQb->getQuery()->getDQL())))
            ->setParameters([
                'category' => $category,
                'scopes' => $scopes
            ]);

        return array_map('current', $qb->getQuery()->getScalarResult());
    }

    /**
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    protected function restrictStaticFallback(QueryBuilder $qb)
    {
        return $qb->andWhere($qb->expr()->neq('cv.visibility', ':parentCategory'))
            ->setParameter('parentCategory', AccountGroupCategoryVisibility::PARENT_CATEGORY);
    }

    /**
     * {@inheritdoc}
     */
    protected function restrictToParentFallback(QueryBuilder $qb)
    {
        return $qb->andWhere($qb->expr()->eq('cv.visibility', ':parentCategory'))
            ->setParameter('parentCategory', AccountGroupCategoryVisibility::PARENT_CATEGORY);
    }

    /**
     * @param array $categoryIds
     * @param int $visibility
     * @param array $scopes
     */
    protected function updateProductVisibilityByCategory(array $categoryIds, $visibility, array $scopes)
    {
        if (!$categoryIds) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\AccountGroupProductVisibilityResolved')
            ->createQueryBuilder();

        $qb->update('OroVisibilityBundle:VisibilityResolved\AccountGroupProductVisibilityResolved', 'agpvr')
            ->set('agpvr.visibility', $visibility)
            ->where($qb->expr()->in('agpvr.scope', ':scopes'))
            ->andWhere($qb->expr()->in('IDENTITY(agpvr.category)', ':categoryIds'))
            ->setParameters(['scopes' => $scopes, 'categoryIds' => $categoryIds]);

        $qb->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function joinCategoryVisibility(QueryBuilder $qb, $target)
    {
        return $qb->leftJoin(
            'OroVisibilityBundle:Visibility\AccountGroupCategoryVisibility',
            'cv',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('node', 'cv.category'),
                $qb->expr()->eq('cv.scope', ':scope')
            )
        )
            ->setParameter('scope', $target);
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved');
    }

    /**
     * @return AccountGroupCategoryRepository
     */
    protected function getRepository()
    {
        return $this->getEntityManager()
            ->getRepository('OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved');
    }
}
