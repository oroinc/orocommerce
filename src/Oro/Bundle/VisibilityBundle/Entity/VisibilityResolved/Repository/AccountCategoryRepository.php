<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;

/**
 * Composite primary key fields order:
 *  - account
 *  - category
 */
class AccountCategoryRepository extends EntityRepository
{
    use CategoryVisibilityResolvedTermTrait;
    use BasicOperationRepositoryTrait;

    /**
     * @param Category $category
     * @param Scope $accountScope
     * @param Scope|null $accountGroupScope
     * @return int visible|hidden|config
     */
    public function getFallbackToAccountVisibility(
        Category $category,
        Scope $accountScope,
        Scope $accountGroupScope = null
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

        if ($accountGroupScope) {
            $qb->select('COALESCE(' .
                'acvr.visibility, agcvr.visibility, cvr.visibility, ' . $qb->expr()->literal($configFallback) .
            ')')
                ->leftJoin(
                    'OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved',
                    'agcvr',
                    Join::WITH,
                    $qb->expr()->andX(
                        $qb->expr()->eq('agcvr.category', 'category'),
                        $qb->expr()->eq('agcvr.scope', ':accountGroupScope')
                    )
                )
                ->setParameter('accountGroupScope', $accountGroupScope);
        }

        $qb
            ->leftJoin(
                'OroVisibilityBundle:VisibilityResolved\AccountCategoryVisibilityResolved',
                'acvr',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('acvr.category', 'category'),
                    $qb->expr()->eq('acvr.scope', ':scope')
                )
            )
            ->where($qb->expr()->eq('category', ':category'))
            ->setParameter('category', $category)
            ->setParameter('scope', $accountScope);

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
            $terms[] = $this->getAccountGroupCategoryVisibilityResolvedTerm($qb, $groupScope, $configValue);
        }
        $terms[] = $this->getAccountCategoryVisibilityResolvedTerm($qb, $scope, $configValue);

        if ($visibility === BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE) {
            $qb->andWhere($qb->expr()->gt(implode(' + ', $terms), 0));
        } else {
            $qb->andWhere($qb->expr()->lte(implode(' + ', $terms), 0));
        }

        $categoryVisibilityResolved = $qb->getQuery()->getArrayResult();

        return array_map('current', $categoryVisibilityResolved);
    }

    /**
     * @param Category $category
     * @param array $accountIds
     * @return array
     */
    public function getVisibilitiesForAccounts(Category $category, array $accountIds)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $configFallback = BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;

        $qb->select(
            'account.id as accountId',
            'COALESCE(' .
            'acvr.visibility, agcvr.visibility, cvr.visibility, ' . $qb->expr()->literal($configFallback) .
            ') as visibility'
        )
        ->from('OroAccountBundle:Account', 'account')
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\AccountCategoryVisibilityResolved',
            'acvr',
            Join::WITH,
            $qb->expr()->eq('acvr.category', ':category')
        )
        ->leftJoin('acvr.scope', 'acvr_scope')
        ->andWhere('acvr.visibility IS NULL OR acvr_scope.account = account')
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved',
            'agcvr',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('agcvr.category', ':category')
            )
        )
        ->leftJoin('agcvr.scope', 'agcvr_scope')
        ->andWhere('agcvr.visibility IS NULL OR agcvr_scope.accountGroup = account.group')
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved',
            'cvr',
            Join::WITH,
            $qb->expr()->eq('cvr.category', ':category')
        )
        ->andWhere($qb->expr()->in('account', ':accountIds'))
        ->setParameter('category', $category)
        ->setParameter('accountIds', $accountIds);

        $fallBackToAccountVisibilities = [];
        foreach ($qb->getQuery()->getArrayResult() as $resultItem) {
            $fallBackToAccountVisibilities[(int)$resultItem['accountId']] = (int)$resultItem['visibility'];
        }

        return $fallBackToAccountVisibilities;
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
        $visibility = $this->getFallbackToAccountVisibility($category, $scope, $groupScope);
        if ($visibility === AccountCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG) {
            $visibility = $configValue;
        }

        return $visibility === AccountCategoryVisibilityResolved::VISIBILITY_VISIBLE;
    }

    /**
     * @param Category $category
     * @param Scope $scope
     * @return null|AccountCategoryVisibilityResolved
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

    /**
     * @param InsertFromSelectQueryExecutor $insertExecutor
     */
    public function insertStaticValues(InsertFromSelectQueryExecutor $insertExecutor)
    {
        $visibilityCondition = sprintf(
            "CASE WHEN acv.visibility = '%s' THEN %s ELSE %s END",
            AccountCategoryVisibility::VISIBLE,
            AccountCategoryVisibilityResolved::VISIBILITY_VISIBLE,
            AccountCategoryVisibilityResolved::VISIBILITY_HIDDEN
        );

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'acv.id',
                'IDENTITY(acv.category)',
                'IDENTITY(acv.scope)',
                $visibilityCondition,
                (string)AccountCategoryVisibilityResolved::SOURCE_STATIC
            )
            ->from('OroVisibilityBundle:Visibility\AccountCategoryVisibility', 'acv')
            ->where('acv.visibility IN (:staticVisibilities)')
            ->setParameter(
                'staticVisibilities',
                [AccountCategoryVisibility::VISIBLE, AccountCategoryVisibility::HIDDEN]
            );

        $insertExecutor->execute(
            $this->getClassName(),
            ['sourceCategoryVisibility', 'category', 'scope', 'visibility', 'source'],
            $queryBuilder
        );
    }

    /**
     * @param InsertFromSelectQueryExecutor $insertExecutor
     */
    public function insertCategoryValues(InsertFromSelectQueryExecutor $insertExecutor)
    {
        $visibilityCondition = sprintf(
            'CASE WHEN cvr.visibility IS NOT NULL THEN cvr.visibility ELSE %s END',
            AccountCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG
        );

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'acv.id',
                'IDENTITY(acv.category)',
                'IDENTITY(acv.scope)',
                $visibilityCondition,
                (string)AccountCategoryVisibilityResolved::SOURCE_STATIC
            )
            ->from('OroVisibilityBundle:Visibility\AccountCategoryVisibility', 'acv')
            ->leftJoin(
                'OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved',
                'cvr',
                'WITH',
                'acv.category = cvr.category'
            )
            ->where('acv.visibility = :category')
            ->setParameter('category', AccountCategoryVisibility::CATEGORY);

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
        ->from('OroVisibilityBundle:Visibility\AccountCategoryVisibility', 'acv')
        ->join('acv.scope', 'acv_scope')
        ->join('acv_scope.account', 'a')
        // join to category that includes only parent category entities
        ->innerJoin('acv.category', 'c')
        // join to related account
        // join to parent category visibility
        ->leftJoin(
            'OroVisibilityBundle:Visibility\AccountCategoryVisibility',
            'acv_parent',
            'WITH',
            'acv_parent.scope = acv.scope AND acv_parent.category = c.parentCategory'
        )
        // join to resolved group visibility for parent category
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved',
            'agcvr_parent',
            'WITH',
            'agcvr_parent.category = c.parentCategory'
        )
        ->leftJoin('agcvr_parent.scope', 'agcvr_parent_scope')
        ->andWhere('(agcvr_parent.visibility IS NULL OR agcvr_parent_scope.accountGroup = a.group)')
        // join to resolved category visibility for parent category
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved',
            'cvr_parent',
            'WITH',
            'cvr_parent.category = c.parentCategory'
        )
        ->andWhere('acv.visibility = ' . $qb->expr()->literal(AccountCategoryVisibility::PARENT_CATEGORY))
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

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'acv.id',
                'IDENTITY(acv.category)',
                'IDENTITY(acv.account)',
                (string)$visibility,
                (string)AccountCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                'IDENTITY(acv.scope)'
            )
            ->from('OroVisibilityBundle:Visibility\AccountCategoryVisibility', 'acv')
            ->andWhere('acv.visibility = :parentCategory')  // parent category fallback
            ->andWhere('acv.id IN (:visibilityIds)')        // specific visibility entity IDs
            ->setParameter('parentCategory', AccountCategoryVisibility::PARENT_CATEGORY);

        foreach (array_chunk($visibilityIds, CategoryRepository::INSERT_BATCH_SIZE) as $ids) {
            $queryBuilder->setParameter('visibilityIds', $ids);
            $insertExecutor->execute(
                $this->getClassName(),
                ['sourceCategoryVisibility', 'category', 'account', 'visibility', 'source', 'scope'],
                $queryBuilder
            );
        }
    }

    /**
     * @param array $categoryIds
     * @param int $visibility
     * @param Scope $scope
     */
    public function updateAccountCategoryVisibilityByCategory(Scope $scope, array $categoryIds, $visibility)
    {
        if (!$categoryIds) {
            return;
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->update('OroVisibilityBundle:VisibilityResolved\AccountCategoryVisibilityResolved', 'acvr')
            ->set('acvr.visibility', $visibility)
            ->where($qb->expr()->eq('acvr.scope', ':scope'))
            ->andWhere($qb->expr()->in('IDENTITY(acvr.category)', ':categoryIds'))
            ->setParameters(['scope' => $scope, 'categoryIds' => $categoryIds]);

        $qb->getQuery()->execute();
    }
}
