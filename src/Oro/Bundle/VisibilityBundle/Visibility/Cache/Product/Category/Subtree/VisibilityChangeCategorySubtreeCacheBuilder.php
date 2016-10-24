<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\Subtree;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupCategoryVisibility;

class VisibilityChangeCategorySubtreeCacheBuilder extends AbstractRelatedEntitiesAwareSubtreeCacheBuilder
{
    /**
     * @param Category $category
     * @param int $visibility
     */
    public function resolveVisibilitySettings(Category $category, $visibility)
    {
        $childCategoryIds = $this->getChildCategoryIdsForUpdate($category);

        $this->registry->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->updateCategoryVisibilityByCategory($childCategoryIds, $visibility);

        $categoryIds = $this->getCategoryIdsForUpdate($category, $childCategoryIds);
        $this->updateProductVisibilityByCategory($categoryIds, $visibility);

        $this->updateProductVisibilitiesForCategoryRelatedEntities($category, $visibility);

        $this->clearChangedEntities();
    }

    /**
     * {@inheritdoc}
     */
    protected function updateAccountGroupsFirstLevel(Category $category, $visibility)
    {
        $accountGroupIds = $this->getAccountGroupIdsFirstLevel($category);
        if ($accountGroupIds === null) {
            return [];
        }

        $this->updateAccountGroupsProductVisibility($category, $accountGroupIds, $visibility);
        $this->updateAccountGroupsCategoryVisibility($category, $accountGroupIds, $visibility);

        return $accountGroupIds;
    }

    /**
     * Get accounts groups with account visibility fallback to 'Visibility To All' for current category
     *
     * @param Category $category
     * @return array
     */
    protected function getAccountGroupIdsFirstLevel(Category $category)
    {
        return $this->getAccountGroupIdsWithFallbackToAll($category);
    }

    /**
     * {@inheritdoc}
     */
    protected function updateAccountsFirstLevel(Category $category, $visibility)
    {
        $accountIdsForUpdate = $this->getAccountIdsFirstLevel($category);

        if ($accountIdsForUpdate === null) {
            return [];
        }

        /**
         * Cache updated account for current category into appropriate section
         */
        $this->accountIdsWithChangedVisibility[$category->getId()] = $accountIdsForUpdate;

        $this->updateAccountsProductVisibility($category, $accountIdsForUpdate, $visibility);
        $this->updateAccountsCategoryVisibility($category, $accountIdsForUpdate, $visibility);

        return $accountIdsForUpdate;
    }

    /**
     * Get accounts with account group visibility fallback to 'Visibility To All' for current category
     *
     * @param Category $category
     * @return array
     */
    protected function getAccountIdsFirstLevel(Category $category)
    {
        $accountIdsForUpdate = $this->getAccountIdsWithFallbackToAll($category);
        $accountGroupIdsForUpdate = $this->accountGroupIdsWithChangedVisibility[$category->getId()];
        if (!empty($accountGroupIdsForUpdate)) {
            $accountIdsForUpdate = array_merge(
                $accountIdsForUpdate,
                /**
                 * Get accounts with account visibility fallback to 'Account Group'
                 * for account groups with fallback 'Visibility To All'
                 * for current category
                 */
                $this->getAccountIdsForUpdate($category, $accountGroupIdsForUpdate)
            );
        }

        return $accountIdsForUpdate;
    }

    /**
     * @param Category $category
     * @return array
     */
    protected function getAccountGroupIdsWithFallbackToAll(Category $category)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroCustomerBundle:AccountGroup')
            ->createQueryBuilder();

        /** @var QueryBuilder $subQb */
        $subQb = $this->registry
            ->getManagerForClass(AccountGroupCategoryVisibility::class)
            ->createQueryBuilder();
        $subQb->select('1')
            ->from(AccountGroupCategoryVisibility::class, 'accountGroupCategoryVisibility')
            ->join('accountGroupCategoryVisibility.scope', 'scope')
            ->where($qb->expr()->eq('accountGroupCategoryVisibility.category', ':category'))
            ->andWhere('scope.accountGroup = accountGroup.id');

        $qb->select('accountGroup.id')
            ->from('OroCustomerBundle:AccountGroup', 'accountGroup')
            ->where($qb->expr()->not($qb->expr()->exists($subQb->getQuery()->getDQL())))
            ->setParameter('category', $category);

        return array_map('current', $qb->getQuery()->getScalarResult());
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
        if (!$categoryIds) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\ProductVisibilityResolved')
            ->createQueryBuilder();

        $qb->update('OroVisibilityBundle:VisibilityResolved\ProductVisibilityResolved', 'pvr')
            ->set('pvr.visibility', $visibility)
            ->andWhere($qb->expr()->in('IDENTITY(pvr.category)', ':categoryIds'))
            ->setParameter('categoryIds', $categoryIds);

        $qb->getQuery()->execute();
    }

    /**
     * @param array $categoryIds
     * @param int $visibility
     */
    protected function updateCategoryVisibilityByCategory(array $categoryIds, $visibility)
    {
        if (!$categoryIds) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->createQueryBuilder();

        $qb->update('OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved', 'cvr')
            ->set('cvr.visibility', $visibility)
            ->andWhere($qb->expr()->in('IDENTITY(cvr.category)', ':categoryIds'))
            ->setParameter('categoryIds', $categoryIds);

        $qb->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function joinCategoryVisibility(QueryBuilder $qb, $target)
    {
        return $qb->leftJoin(
            'OroVisibilityBundle:Visibility\CategoryVisibility',
            'cv',
            Join::WITH,
            $qb->expr()->eq('node', 'cv.category')
        );
    }
}
