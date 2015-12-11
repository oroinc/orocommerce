<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class VisibilityChangeGroupSubtreeCacheBuilder extends AbstractRelatedEntitiesAwareSubtreeCacheBuilder
{
    /** @var AccountGroup */
    protected $accountGroup;

    /**
     * @param Category $category
     * @param AccountGroup $accountGroup
     */
    public function resolveVisibilitySettings(Category $category, AccountGroup $accountGroup)
    {
        $visibility = $this->categoryVisibilityResolver->isCategoryVisibleForAccountGroup($category, $accountGroup);
        $visibility = $this->convertVisibility($visibility);

        $categoryIds = $this->getCategoryIdsForUpdate($category, $accountGroup);
        $this->updateProductVisibilityByCategory($categoryIds, $visibility, $accountGroup);

        $this->accountGroup = $accountGroup;

        $this->updateProductVisibilitiesForCategoryRelatedEntities($category, $visibility);

        $this->clearChangedEntities();
    }

    /**
     * {@inheritdoc}
     */
    protected function updateAccountGroupsFirstLevel(Category $category, $visibility)
    {
        return [$this->accountGroup];
    }

    /**
     * {@inheritdoc}
     */
    protected function updateAccountsFirstLevel(Category $category, $visibility)
    {
        $accountsForUpdate = $this->getAccountsWithFallbackToCurrentGroup($category, $this->accountGroup);
        $this->updateAccountsProductVisibility($category, $accountsForUpdate, $visibility);

        return $accountsForUpdate;
    }

    /**
     * @param Category $category
     * @param AccountGroup $accountGroup
     * @return Account[]
     */
    protected function getAccountsWithFallbackToCurrentGroup(Category $category, AccountGroup $accountGroup)
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

        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:Account')
            ->createQueryBuilder();

        /** @var QueryBuilder $subQueryQb */
        $subQueryQb = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:Visibility\AccountCategoryVisibility')
            ->createQueryBuilder();

        $subQuery = $subQueryQb->select('IDENTITY(accountCategoryVisibility.account)')
            ->from(
                'OroB2BAccountBundle:Visibility\AccountCategoryVisibility',
                'accountCategoryVisibility'
            )
            ->where($subQueryQb->expr()->eq('accountCategoryVisibility.category', ':category'))
            ->distinct();

        $qb->select('account')
            ->from('OroB2BAccountBundle:Account', 'account')
            ->where($qb->expr()->notIn(
                'account',
                $subQuery->getDQL()
            ))
            ->andWhere($qb->expr()->in('account', $groupAccountIds))
            ->setParameter('category', $category);

        return $qb->getQuery()->getResult();
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
     * @param AccountGroup $accountGroup
     */
    protected function updateProductVisibilityByCategory(array $categoryIds, $visibility, AccountGroup $accountGroup)
    {
        if (!$categoryIds) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved')
            ->createQueryBuilder();

        $qb->update('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved', 'agpvr')
            ->set('agpvr.visibility', $visibility)
            ->where($qb->expr()->eq('agpvr.accountGroup', ':accountGroup'))
            ->andWhere($qb->expr()->in('IDENTITY(agpvr.category)', ':categoryIds'))
            ->setParameters(['accountGroup' => $accountGroup, 'categoryIds' => $categoryIds]);

        $qb->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function joinCategoryVisibility(QueryBuilder $qb, $target = null)
    {
        return $qb->leftJoin(
            'OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility',
            'cv',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('node', 'cv.category'),
                $qb->expr()->eq('cv.accountGroup', ':accountGroup')
            )
        )
            ->setParameter('accountGroup', $target);
    }
}
