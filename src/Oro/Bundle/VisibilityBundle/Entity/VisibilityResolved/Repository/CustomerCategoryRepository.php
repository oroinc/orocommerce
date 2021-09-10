<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerCategoryVisibilityResolved;

/**
 * Composite primary key fields order:
 *  - scope
 *  - category
 */
class CustomerCategoryRepository extends ServiceEntityRepository
{
    use CategoryVisibilityResolvedTermTrait;
    use BasicOperationRepositoryTrait;

    /**
     * @param Category $category
     * @param Scope $customerScope
     * @param Scope|null $customerGroupScope
     * @return int visible|hidden|config
     */
    public function getFallbackToCustomerVisibility(
        Category $category,
        Scope $customerScope,
        Scope $customerGroupScope = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $configFallback = BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        $qb->select('COALESCE(acvr.visibility, cvr.visibility, ' . $qb->expr()->literal($configFallback) . ')')
            ->from('OroCatalogBundle:Category', 'category')
            ->leftJoin(
                'OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved',
                'cvr',
                Join::WITH,
                $qb->expr()->eq('cvr.category', 'category')
            );

        if ($customerGroupScope) {
            $qb->select('COALESCE(' .
                'acvr.visibility, agcvr.visibility, cvr.visibility, ' . $qb->expr()->literal($configFallback) .
            ')')
                ->leftJoin(
                    'OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved',
                    'agcvr',
                    Join::WITH,
                    $qb->expr()->andX(
                        $qb->expr()->eq('agcvr.category', 'category'),
                        $qb->expr()->eq('agcvr.scope', ':customerGroupScope')
                    )
                )
                ->setParameter('customerGroupScope', $customerGroupScope);
        }

        $qb
            ->leftJoin(
                'OroVisibilityBundle:VisibilityResolved\CustomerCategoryVisibilityResolved',
                'acvr',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('acvr.category', 'category'),
                    $qb->expr()->eq('acvr.scope', ':scope')
                )
            )
            ->where($qb->expr()->eq('category', ':category'))
            ->setParameter('category', $category)
            ->setParameter('scope', $customerScope);

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int $visibility
     * @param int $configValue
     * @param Scope $scope
     * @param Scope $groupScope
     * @return array
     */
    public function getCategoryIdsByVisibility($visibility, $configValue, Scope $scope, Scope $groupScope = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('category.id')
            ->from('OroCatalogBundle:Category', 'category')
            ->orderBy('category.id');

        $terms = [$this->getCategoryVisibilityResolvedTerm($qb, $configValue)];
        if ($groupScope) {
            $terms[] = $this->getCustomerGroupCategoryVisibilityResolvedTerm($qb, $groupScope, $configValue);
        }
        $terms[] = $this->getCustomerCategoryVisibilityResolvedTerm($qb, $scope, $configValue);

        if ($visibility === BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE) {
            $qb->andWhere($qb->expr()->gt(implode(' + ', $terms), 0));
        } else {
            $qb->andWhere($qb->expr()->lte(implode(' + ', $terms), 0));
        }

        $categoryVisibilityResolved = $qb->getQuery()->getArrayResult();

        return array_map('current', $categoryVisibilityResolved);
    }

    /**
     * @param ScopeManager $scopeManager
     * @param Category $category
     * @param array $customerIds
     * @return array
     */
    public function getVisibilitiesForCustomers(ScopeManager $scopeManager, Category $category, array $customerIds)
    {
        $configFallback = BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select(
            'customer.id as customerId',
            'COALESCE(' .
            'acvr.visibility, agcvr.visibility, cvr.visibility, ' . $qb->expr()->literal($configFallback) .
            ') as visibility'
        )
        ->from('OroCustomerBundle:Customer', 'customer')
        ->leftJoin('OroScopeBundle:Scope', 'acvr_scope', 'WITH', 'acvr_scope.customer = customer')
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\CustomerCategoryVisibilityResolved',
            'acvr',
            Join::WITH,
            'acvr.category = :category AND acvr_scope = acvr.scope'
        )
        ->leftJoin('OroScopeBundle:Scope', 'agcvr_scope', 'WITH', 'agcvr_scope.customerGroup = customer.group')
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved',
            'agcvr',
            Join::WITH,
            'agcvr.category = :category AND agcvr_scope = agcvr.scope'
        )
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved',
            'cvr',
            Join::WITH,
            $qb->expr()->eq('cvr.category', ':category')
        )
        ->andWhere($qb->expr()->in('customer', ':customerIds'))
        ->setParameter('category', $category)
        ->setParameter('customerIds', $customerIds);

        $scopeManager
            ->getCriteriaForRelatedScopes(CustomerCategoryVisibility::VISIBILITY_TYPE, [])
            ->applyToJoin($qb, 'acvr_scope');

        $scopeManager
            ->getCriteriaForRelatedScopes(CustomerGroupCategoryVisibility::VISIBILITY_TYPE, [])
            ->applyToJoin($qb, 'agcvr_scope');

        $fallBackToCustomerVisibilities = [];
        $arrayResult = $qb->getQuery()->getArrayResult();
        foreach ($arrayResult as $resultItem) {
            $fallBackToCustomerVisibilities[(int)$resultItem['customerId']] = (int)$resultItem['visibility'];
        }

        return $fallBackToCustomerVisibilities;
    }

    /**
     * @param Category $category
     * @param Scope $scope
     * @param Scope $groupScope
     * @param int $configValue
     * @return bool
     */
    public function isCategoryVisible(Category $category, $configValue, Scope $scope, Scope $groupScope = null)
    {
        $visibility = $this->getFallbackToCustomerVisibility($category, $scope, $groupScope);
        if ($visibility === CustomerCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG) {
            $visibility = $configValue;
        }

        return $visibility === CustomerCategoryVisibilityResolved::VISIBILITY_VISIBLE;
    }

    /**
     * @param Category $category
     * @param Scope $scope
     * @return null|CustomerCategoryVisibilityResolved
     */
    public function findByPrimaryKey(Category $category, Scope $scope)
    {
        return $this->findOneBy(['scope' => $scope, 'category' => $category]);
    }

    public function clearTable()
    {
        // TRUNCATE can't be used because it can't be rolled back in case of DB error
        $this->createQueryBuilder('acvr')
            ->delete()
            ->getQuery()
            ->execute();
    }

    public function insertStaticValues(InsertFromSelectQueryExecutor $insertExecutor)
    {
        $visibilityCondition = sprintf(
            "CASE WHEN acv.visibility = '%s' THEN %s ELSE %s END",
            CustomerCategoryVisibility::VISIBLE,
            CustomerCategoryVisibilityResolved::VISIBILITY_VISIBLE,
            CustomerCategoryVisibilityResolved::VISIBILITY_HIDDEN
        );

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'acv.id',
                'IDENTITY(acv.category)',
                'IDENTITY(acv.scope)',
                $visibilityCondition,
                (string)CustomerCategoryVisibilityResolved::SOURCE_STATIC
            )
            ->from('OroVisibilityBundle:Visibility\CustomerCategoryVisibility', 'acv')
            ->where('acv.visibility IN (:staticVisibilities)')
            ->setParameter(
                'staticVisibilities',
                [CustomerCategoryVisibility::VISIBLE, CustomerCategoryVisibility::HIDDEN]
            );

        $insertExecutor->execute(
            $this->getClassName(),
            ['sourceCategoryVisibility', 'category', 'scope', 'visibility', 'source'],
            $queryBuilder
        );
    }

    public function insertCategoryValues(InsertFromSelectQueryExecutor $insertExecutor)
    {
        $visibilityCondition = sprintf(
            'CASE WHEN cvr.visibility IS NOT NULL THEN cvr.visibility ELSE %s END',
            CustomerCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG
        );

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'acv.id',
                'IDENTITY(acv.category)',
                'IDENTITY(acv.scope)',
                $visibilityCondition,
                (string)CustomerCategoryVisibilityResolved::SOURCE_STATIC
            )
            ->from('OroVisibilityBundle:Visibility\CustomerCategoryVisibility', 'acv')
            ->leftJoin(
                'OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved',
                'cvr',
                'WITH',
                'acv.category = cvr.category'
            )
            ->where('acv.visibility = :category')
            ->setParameter('category', CustomerCategoryVisibility::CATEGORY);

        $insertExecutor->execute(
            $this->getClassName(),
            ['sourceCategoryVisibility', 'category', 'scope', 'visibility', 'source'],
            $queryBuilder
        );
    }

    /**
     * [
     *      [
     *          'visibility_id' => <int>,
     *          'parent_visibility_id' => <int|null>,
     *          'parent_visibility' => <string|null>,
     *          'category_id' => <int>,
     *          'parent_category_id' => <int|null>,
     *          'parent_group_resolved_visibility' => <int|null>,
     *          'parent_category_resolved_visibility' => <int|null>
     *      ],
     *      ...
     * ]
     *
     * @return array
     */
    public function getParentCategoryVisibilities()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $query = $qb->select(
            'acv.id as visibility_id',
            'acv_parent.id as parent_visibility_id',
            'acv_parent.visibility as parent_visibility',
            'c.id as category_id',
            'IDENTITY(c.parentCategory) as parent_category_id',
            'agcvr_parent.visibility as parent_group_resolved_visibility',
            'cvr_parent.visibility as parent_category_resolved_visibility'
        )
        ->from('OroVisibilityBundle:Visibility\CustomerCategoryVisibility', 'acv')
        ->join('acv.scope', 'acv_scope')
        ->join('acv_scope.customer', 'a')
        // join to category that includes only parent category entities
        ->innerJoin('acv.category', 'c')
        // join to parent category visibility
        ->leftJoin(
            'OroVisibilityBundle:Visibility\CustomerCategoryVisibility',
            'acv_parent',
            'WITH',
            'acv_parent.scope = acv.scope AND acv_parent.category = c.parentCategory'
        )
        // join to resolved group visibility for parent category
        ->leftJoin('OroScopeBundle:Scope', 'agcvr_parent_scope', 'WITH', 'a.group = agcvr_parent_scope.customerGroup')
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved',
            'agcvr_parent',
            'WITH',
            'agcvr_parent.category = c.parentCategory AND agcvr_parent.scope = agcvr_parent_scope'
        )
        // join to resolved category visibility for parent category
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved',
            'cvr_parent',
            'WITH',
            'cvr_parent.category = c.parentCategory'
        )
        ->andWhere('acv.visibility = ' . $qb->expr()->literal(CustomerCategoryVisibility::PARENT_CATEGORY))
        // order is important to make sure that higher level categories will be processed first
        ->addOrderBy('c.level', 'ASC')
        ->addOrderBy('c.left', 'ASC')
        ->getQuery();

        return $query->getScalarResult();
    }

    /**
     * @param InsertFromSelectQueryExecutor $insertExecutor
     * @param array $visibilityIds
     * @param int $visibility
     */
    public function insertParentCategoryValues(
        InsertFromSelectQueryExecutor $insertExecutor,
        array $visibilityIds,
        $visibility
    ) {
        if (!$visibilityIds) {
            return;
        }

        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder
            ->select(
                'acv.id',
                'IDENTITY(acv.category)',
                (string)$queryBuilder->expr()->literal($visibility),
                (string)CustomerCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                'IDENTITY(acv.scope)'
            )
            ->from('OroVisibilityBundle:Visibility\CustomerCategoryVisibility', 'acv')
            ->andWhere('acv.visibility = :parentCategory')  // parent category fallback
            ->andWhere('acv.id IN (:visibilityIds)')        // specific visibility entity IDs
            ->setParameter('parentCategory', CustomerCategoryVisibility::PARENT_CATEGORY);

        foreach (array_chunk($visibilityIds, CategoryRepository::INSERT_BATCH_SIZE) as $ids) {
            $queryBuilder->setParameter('visibilityIds', $ids);
            $insertExecutor->execute(
                $this->getClassName(),
                ['sourceCategoryVisibility', 'category', 'visibility', 'source', 'scope'],
                $queryBuilder
            );
        }
    }

    /**
     * @param array $categoryIds
     * @param int $visibility
     * @param Scope $scope
     */
    public function updateCustomerCategoryVisibilityByCategory(Scope $scope, array $categoryIds, $visibility)
    {
        if (!$categoryIds) {
            return;
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->update('OroVisibilityBundle:VisibilityResolved\CustomerCategoryVisibilityResolved', 'acvr')
            ->set('acvr.visibility', ':visibility')
            ->where($qb->expr()->eq('acvr.scope', ':scope'))
            ->andWhere($qb->expr()->in('IDENTITY(acvr.category)', ':categoryIds'))
            ->setParameters(['scope' => $scope, 'categoryIds' => $categoryIds, 'visibility' => $visibility]);

        $qb->getQuery()->execute();
    }
}
