<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerGroupCategoryVisibilityResolved;

/**
 * Composite primary key fields order:
 *  - scope
 *  - category
 */
class CustomerGroupCategoryRepository extends ServiceEntityRepository
{
    use CategoryVisibilityResolvedTermTrait;
    use BasicOperationRepositoryTrait;

    /**
     * @param Category $category
     * @param int $configValue
     * @param Scope $scope
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isCategoryVisible(Category $category, $configValue, Scope $scope)
    {
        $visibility = $this->getFallbackToGroupVisibility($category, $scope);
        if ($visibility === CustomerGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG) {
            $visibility = $configValue;
        }

        return $visibility === CustomerGroupCategoryVisibilityResolved::VISIBILITY_VISIBLE;
    }

    /**
     * @param int $visibility
     * @param Scope $scope
     * @param int $configValue
     * @return array
     */
    public function getCategoryIdsByVisibility($visibility, Scope $scope, $configValue)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('category.id')
            ->from('OroCatalogBundle:Category', 'category')
            ->orderBy('category.id');

        $terms = [
            $this->getCategoryVisibilityResolvedTerm($qb, $configValue),
            $this->getCustomerGroupCategoryVisibilityResolvedTerm($qb, $scope, $configValue)
        ];

        if ($visibility === BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE) {
            $qb->andWhere($qb->expr()->gt(implode(' + ', $terms), 0));
        } else {
            $qb->andWhere($qb->expr()->lte(implode(' + ', $terms), 0));
        }

        $categoryVisibilityResolved = $qb->getQuery()->getArrayResult();

        return array_map('current', $categoryVisibilityResolved);
    }

    public function clearTable()
    {
        // TRUNCATE can't be used because it can't be rolled back in case of DB error
        $this->createQueryBuilder('agcvr')
            ->delete()
            ->getQuery()
            ->execute();
    }

    public function insertStaticValues(InsertFromSelectQueryExecutor $insertExecutor)
    {
        $visibilityCondition = sprintf(
            "CASE WHEN agcv.visibility = '%s' THEN %s ELSE %s END",
            CustomerGroupCategoryVisibility::VISIBLE,
            CustomerGroupCategoryVisibilityResolved::VISIBILITY_VISIBLE,
            CustomerGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN
        );

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'agcv.id',
                'IDENTITY(agcv.category)',
                'IDENTITY(agcv.scope)',
                $visibilityCondition,
                (string)CustomerGroupCategoryVisibilityResolved::SOURCE_STATIC
            )
            ->from('OroVisibilityBundle:Visibility\CustomerGroupCategoryVisibility', 'agcv')
            ->where('agcv.visibility != :parentCategory')
            ->setParameter('parentCategory', CustomerGroupCategoryVisibility::PARENT_CATEGORY);

        $insertExecutor->execute(
            $this->getClassName(),
            ['sourceCategoryVisibility', 'category', 'scope', 'visibility', 'source'],
            $queryBuilder
        );
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
        $visibility = (int)$visibility;
        if (!$visibilityIds) {
            return;
        }

        $sourceCondition = sprintf(
            'CASE WHEN c.parentCategory IS NOT NULL THEN %s ELSE %s END',
            CustomerGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
            CustomerGroupCategoryVisibilityResolved::SOURCE_STATIC
        );

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'agcv.id',
                'IDENTITY(agcv.category)',
                (string)$visibility,
                $sourceCondition,
                'IDENTITY(agcv.scope)'
            )
            ->from('OroVisibilityBundle:Visibility\CustomerGroupCategoryVisibility', 'agcv')
            ->leftJoin('agcv.category', 'c')
            ->andWhere('agcv.visibility = :parentCategory') // parent category fallback
            ->andWhere('agcv.id IN (:visibilityIds)')       // specific visibility entity IDs
            ->setParameter('parentCategory', CustomerGroupCategoryVisibility::PARENT_CATEGORY);

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
     * @param Category $category
     * @param Scope $scope
     * @return int visible|hidden|config
     */
    public function getFallbackToGroupVisibility(Category $category, Scope $scope)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $configFallback = CustomerGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        $qb->select('COALESCE(agcvr.visibility, cvr.visibility, '. $qb->expr()->literal($configFallback).')')
            ->from('OroCatalogBundle:Category', 'category')
            ->leftJoin(
                'OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved',
                'cvr',
                Join::WITH,
                $qb->expr()->eq('cvr.category', 'category')
            )
            ->leftJoin(
                'OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved',
                'agcvr',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('agcvr.category', 'category'),
                    $qb->expr()->eq('agcvr.scope', ':scope')
                )
            )
            ->where($qb->expr()->eq('category', ':category'))
            ->setParameters([
                'category' => $category,
                'scope' => $scope
            ]);

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param ScopeManager $scopeManager
     * @param Category $category
     * @param array $customerGroupIds
     * @return array
     */
    public function getVisibilitiesForCustomerGroups(
        ScopeManager $scopeManager,
        Category $category,
        array $customerGroupIds
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $configFallback = CustomerGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;

        $qb->select(
            'IDENTITY(scope.customerGroup) as customerGroupId',
            'COALESCE(agcvr.visibility, cvr.visibility, '. $qb->expr()->literal($configFallback).') as visibility',
            'agcvr.visibility as a',
            'cvr.visibility as b'
        )
        ->from('OroCustomerBundle:CustomerGroup', 'customerGroup')
        ->leftJoin('OroScopeBundle:Scope', 'scope', 'WITH', 'scope.customerGroup = customerGroup')
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved',
            'agcvr',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('agcvr.category', ':category'),
                $qb->expr()->eq('agcvr.scope', 'scope')
            )
        )
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved',
            'cvr',
            Join::WITH,
            $qb->expr()->eq('cvr.category', ':category')
        )
        ->where($qb->expr()->in('customerGroup.id', ':customerGroupIds'))
        ->setParameters([
            'category' => $category,
            'customerGroupIds' => $customerGroupIds
        ]);
        $scopeManager->getCriteriaForRelatedScopes(CustomerGroupCategoryVisibility::VISIBILITY_TYPE, []);

        $fallBackToGroupVisibilities = [];
        $arrayResult = $qb->getQuery()->getArrayResult();
        foreach ($arrayResult as $resultItem) {
            $fallBackToGroupVisibilities[(int)$resultItem['customerGroupId']] = (int)$resultItem['visibility'];
        }

        return $fallBackToGroupVisibilities;
    }

    /**
     * [
     *      [
     *          'visibility_id' => <int>,
     *          'parent_visibility_id' => <int|null>,
     *          'parent_visibility' => <string|null>,
     *          'category_id' => <int>,
     *          'parent_category_id' => <int|null>,
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

        return $qb->select(
            'agcv.id as visibility_id',
            'agcv_parent.id as parent_visibility_id',
            'agcv_parent.visibility as parent_visibility',
            'c.id as category_id',
            'IDENTITY(c.parentCategory) as parent_category_id',
            'cvr_parent.visibility as parent_category_resolved_visibility'
        )
        ->from('OroVisibilityBundle:Visibility\CustomerGroupCategoryVisibility', 'agcv')
        // join to category that includes only parent category entities
        ->innerJoin(
            'agcv.category',
            'c',
            'WITH',
            'agcv.visibility = ' . $qb->expr()->literal(CustomerGroupCategoryVisibility::PARENT_CATEGORY)
        )
        // join to parent category visibility
        ->leftJoin(
            'OroVisibilityBundle:Visibility\CustomerGroupCategoryVisibility',
            'agcv_parent',
            'WITH',
            'agcv_parent.scope = agcv.scope AND agcv_parent.category = c.parentCategory'
        )
        // join to resolved category visibility for parent category
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved',
            'cvr_parent',
            'WITH',
            'cvr_parent.category = c.parentCategory'
        )
        // order is important to make sure that higher level categories will be processed first
        ->addOrderBy('c.level', 'ASC')
        ->addOrderBy('c.left', 'ASC')
        ->getQuery()
        ->getScalarResult();
    }
}
