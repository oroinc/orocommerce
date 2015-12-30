<?php

namespace OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

/**
 * Composite primary key fields order:
 *  - accountGroup
 *  - category
 */
class AccountGroupCategoryRepository extends EntityRepository
{
    use CategoryVisibilityResolvedTermTrait;

    /**
     * @param Category $category
     * @param AccountGroup $accountGroup
     * @param int $configValue
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isCategoryVisible(Category $category, AccountGroup $accountGroup, $configValue)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select($this->formatConfigFallback('agcvr.visibility, cvr.visibility', $configValue))
            ->from('OroB2BCatalogBundle:Category', 'category')
            ->leftJoin(
                'OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved',
                'cvr',
                Join::WITH,
                $qb->expr()->eq('cvr.category', 'category')
            )
            ->leftJoin(
                'OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved',
                'agcvr',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('agcvr.category', 'category'),
                    $qb->expr()->eq('agcvr.accountGroup', ':accountGroup')
                )
            )
            ->where($qb->expr()->eq('category', ':category'))
            ->setParameters([
                'category' => $category,
                'accountGroup' => $accountGroup,
            ]);

        $visibility = $qb->getQuery()->getSingleScalarResult();

        return (int)$visibility === BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE;
    }

    /**
     * @param int $visibility
     * @param AccountGroup $accountGroup
     * @param int $configValue
     * @return array
     */
    public function getCategoryIdsByVisibility($visibility, AccountGroup $accountGroup, $configValue)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('category.id')
            ->from('OroB2BCatalogBundle:Category', 'category')
            ->orderBy('category.id');

        $terms = [
            $this->getCategoryVisibilityResolvedTerm($qb, $configValue),
            $this->getAccountGroupCategoryVisibilityResolvedTerm($qb, $accountGroup, $configValue)
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

    /**
     * @param InsertFromSelectQueryExecutor $insertExecutor
     */
    public function insertStaticValues(InsertFromSelectQueryExecutor $insertExecutor)
    {
        $visibilityCondition = sprintf(
            "CASE WHEN agcv.visibility = '%s' THEN %s ELSE %s END",
            AccountGroupCategoryVisibility::VISIBLE,
            AccountGroupCategoryVisibilityResolved::VISIBILITY_VISIBLE,
            AccountGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN
        );

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'agcv.id',
                'IDENTITY(agcv.category)',
                'IDENTITY(agcv.accountGroup)',
                $visibilityCondition,
                (string)AccountGroupCategoryVisibilityResolved::SOURCE_STATIC
            )
            ->from('OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility', 'agcv')
            ->where('agcv.visibility != :parentCategory')
            ->setParameter('parentCategory', AccountGroupCategoryVisibility::PARENT_CATEGORY);

        $insertExecutor->execute(
            $this->getClassName(),
            ['sourceCategoryVisibility', 'category', 'accountGroup', 'visibility', 'source'],
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
        if (!$visibilityIds) {
            return;
        }

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'agcv.id',
                'IDENTITY(agcv.category)',
                'IDENTITY(agcv.accountGroup)',
                (string)$visibility,
                (string)AccountGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY
            )
            ->from('OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility', 'agcv')
            ->andWhere('agcv.visibility = :parentCategory') // parent category fallback
            ->andWhere('agcv.id IN (:visibilityIds)')       // specific visibility entity IDs
            ->setParameter('parentCategory', AccountGroupCategoryVisibility::PARENT_CATEGORY);

        foreach (array_chunk($visibilityIds, CategoryRepository::INSERT_BATCH_SIZE) as $ids) {
            $queryBuilder->setParameter('visibilityIds', $ids);
            $insertExecutor->execute(
                $this->getClassName(),
                ['sourceCategoryVisibility', 'category', 'accountGroup', 'visibility', 'source'],
                $queryBuilder
            );
        }
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
        ->from('OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility', 'agcv')
        // join to category that includes only parent category entities
        ->innerJoin(
            'agcv.category',
            'c',
            'WITH',
            'agcv.visibility = ' . $qb->expr()->literal(AccountGroupCategoryVisibility::PARENT_CATEGORY)
        )
        // join to parent category visibility
        ->leftJoin(
            'OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility',
            'agcv_parent',
            'WITH',
            'agcv_parent.accountGroup = agcv.accountGroup AND agcv_parent.category = c.parentCategory'
        )
        // join to resolved category visibility for parent category
        ->leftJoin(
            'OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved',
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
