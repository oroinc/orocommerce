<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class VisibilityChangeCategorySubtreeCacheBuilder extends AbstractRelatedEntitiesAwareSubtreeCacheBuilder
{
    /**
     * @param Category $category
     */
    public function resolveVisibilitySettings(Category $category)
    {
        $visibility = $this->categoryVisibilityResolver->isCategoryVisible($category);
        $visibility = $this->convertVisibility($visibility);

        $categoryIds = $this->getCategoryIdsForUpdate($category, null);
        $this->updateProductVisibilityByCategory($categoryIds, $visibility);

        $this->updateProductVisibilitiesForCategoryRelatedEntities($category, $visibility);
    }

    /**
     * {@inheritdoc}
     */
    protected function updateAccountGroupsFirstLevel(Category $category, $visibility)
    {
        $accountGroupsForUpdate = $this->getAccountGroupsFirstLevel($category);
        if ($accountGroupsForUpdate === null) {
            return [];
        }

        $this->updateAccountGroupsProductVisibility($category, $accountGroupsForUpdate, $visibility);

        return $accountGroupsForUpdate;
    }

    /**
     * Get accounts with account visibility fallback to 'Visibility To All' for current category
     *
     * @param Category $category
     * @return AccountGroup[]
     */
    protected function getAccountGroupsFirstLevel(Category $category)
    {
        return $this->getAccountGroupsWithFallbackToAll($category);
    }

    /**
     * {@inheritdoc}
     */
    protected function updateAccountsFirstLevel(Category $category, $visibility)
    {
        $accountsForUpdate = $this->getAccountsFirstLevel($category);

        if ($accountsForUpdate === null) {
            return [];
        }

        /**
         * Cache updated account for current category into appropriate section
         */
        $this->accountsWithChangedVisibility[$category->getId()] = $accountsForUpdate;

        $this->updateAccountsProductVisibility($category, $accountsForUpdate, $visibility);

        return $accountsForUpdate;
    }

    /**
     * Get account groups with account group visibility fallback to 'Visibility To All' for current category
     *
     * @param Category $category
     * @return Account[]
     */
    protected function getAccountsFirstLevel(Category $category)
    {
        $accountsForUpdate = $this->getAccountsWithFallbackToALL($category);
        $accountGroupsForUpdate = $this->accountGroupsWithChangedVisibility[$category->getId()];
        if (!empty($accountGroupsForUpdate)) {
            $updatedAccountGroupIds = [];
            /** @var AccountGroup[] $accountGroupsForUpdate */
            foreach ($accountGroupsForUpdate as $updatedAccountGroup) {
                $updatedAccountGroupIds[] = $updatedAccountGroup->getId();
            }
            $accountsForUpdate = array_merge(
                $accountsForUpdate,
                /**
                 * Get accounts with account visibility fallback to 'Account Group'
                 * for account groups with fallback 'Visibility To All'
                 * for current category
                 */
                $this->getAccountsForUpdate($category, $updatedAccountGroupIds)
            );
        }

        return $accountsForUpdate;
    }

    /**
     * @param Category $category
     * @return AccountGroup[]
     */
    protected function getAccountGroupsWithFallbackToAll(Category $category)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:AccountGroup')
            ->createQueryBuilder();

        /** @var QueryBuilder $subQueryQb */
        $subQueryQb = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility')
            ->createQueryBuilder();

        $subQuery = $subQueryQb->select('IDENTITY(accountGroupCategoryVisibility.accountGroup)')
            ->from(
                'OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility',
                'accountGroupCategoryVisibility'
            )
            ->where($subQueryQb->expr()->eq('accountGroupCategoryVisibility.category', ':category'))
            ->distinct();

        $qb->select('accountGroup')
            ->from('OroB2BAccountBundle:AccountGroup', 'accountGroup')
            ->where($qb->expr()->notIn(
                'accountGroup',
                $subQuery->getDQL()
            ))
            ->setParameter('category', $category);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    protected function restrictStaticFallback(QueryBuilder $qb)
    {
        return $qb->andWhere($qb->expr()->isNotNull('cv.visibility'));
    }

    /**
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    protected function restrictToParentFallback(QueryBuilder $qb)
    {
        return $qb->andWhere($qb->expr()->isNull('cv.visibility'));
    }

    /**
     * @param array $categoryIds
     * @param int $visibility
     */
    protected function updateProductVisibilityByCategory(array $categoryIds, $visibility)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->createQueryBuilder();

        $qb->update('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved', 'pvr')
            ->set('pvr.visibility', $visibility)
            ->andWhere($qb->expr()->in('pvr.categoryId', ':categoryIds'))
            ->setParameter('categoryIds', $categoryIds);

        $qb->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function joinCategoryVisibility(QueryBuilder $qb, $target)
    {
        return $qb->leftJoin(
            'OroB2BAccountBundle:Visibility\CategoryVisibility',
            'cv',
            Join::WITH,
            $qb->expr()->eq('node', 'cv.category')
        );
    }
}
