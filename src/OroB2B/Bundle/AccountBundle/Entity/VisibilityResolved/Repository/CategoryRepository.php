<?php

namespace OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;

/**
 * Composite primary key fields order:
 *  - account
 *  - category
 */
class CategoryRepository extends EntityRepository
{
    use CategoryVisibilityResolvedTermTrait;

    const INSERT_BATCH_SIZE = 500;

    /**
     * @param Category $category
     * @param int $configValue
     * @return bool
     */
    public function isCategoryVisible(Category $category, $configValue)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select($this->formatConfigFallback('cvr.visibility', $configValue))
            ->from('OroB2BCatalogBundle:Category', 'category')
            ->leftJoin(
                'OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved',
                'cvr',
                Join::WITH,
                $qb->expr()->eq('cvr.category', 'category')
            )
            ->where($qb->expr()->eq('category', ':category'))
            ->setParameter('category', $category);

        $visibility = $qb->getQuery()->getSingleScalarResult();

        return (int)$visibility === CategoryVisibilityResolved::VISIBILITY_VISIBLE;
    }

    /**
     * @param int $visibility
     * @param int $configValue
     * @return array
     */
    public function getCategoryIdsByVisibility($visibility, $configValue)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('category.id')
            ->from('OroB2BCatalogBundle:Category', 'category')
            ->orderBy('category.id');

        $terms = [$this->getCategoryVisibilityResolvedTerm($qb, $configValue)];

        if ($visibility === CategoryVisibilityResolved::VISIBILITY_VISIBLE) {
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
        $this->createQueryBuilder('cvr')
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
            "CASE WHEN cv.visibility = '%s' THEN %s ELSE %s END",
            CategoryVisibility::VISIBLE,
            CategoryVisibilityResolved::VISIBILITY_VISIBLE,
            CategoryVisibilityResolved::VISIBILITY_HIDDEN
        );

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'cv.id',
                'IDENTITY(cv.category)',
                $visibilityCondition,
                (string)CategoryVisibilityResolved::SOURCE_STATIC
            )
            ->from('OroB2BAccountBundle:Visibility\CategoryVisibility', 'cv')
            ->where('cv.visibility != :config')
            ->setParameter('config', CategoryVisibility::CONFIG);

        $insertExecutor->execute(
            $this->getClassName(),
            ['sourceCategoryVisibility', 'category', 'visibility', 'source'],
            $queryBuilder
        );
    }

    /**
     * @param InsertFromSelectQueryExecutor $insertExecutor
     * @param array $categoryIds
     * @param int $visibility
     */
    public function insertParentCategoryValues(
        InsertFromSelectQueryExecutor $insertExecutor,
        array $categoryIds,
        $visibility
    ) {
        if (!$categoryIds) {
            return;
        }

        $sourceCondition = sprintf(
            'CASE WHEN c.parentCategory IS NOT NULL THEN %s ELSE %s END',
            CategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
            CategoryVisibilityResolved::SOURCE_STATIC
        );

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'c.id',
                (string)$visibility,
                $sourceCondition
            )
            ->from('OroB2BCatalogBundle:Category', 'c')
            ->leftJoin('OroB2BAccountBundle:Visibility\CategoryVisibility', 'cv', 'WITH', 'cv.category = c')
            ->andWhere('cv.visibility IS NULL')     // parent category fallback
            ->andWhere('c.id IN (:categoryIds)');   // specific category IDs

        foreach (array_chunk($categoryIds, self::INSERT_BATCH_SIZE) as $ids) {
            $queryBuilder->setParameter('categoryIds', $ids);
            $insertExecutor->execute(
                $this->getClassName(),
                ['category', 'visibility', 'source'],
                $queryBuilder
            );
        }
    }

    /**
     * [
     *      [
     *          'category_id' => <int>,
     *          'parent_category_id' => <int|null>,
     *          'resolved_visibility' => <int|null>
     *      ],
     *      ...
     * ]
     *
     * @return array
     */
    public function getCategoriesWithResolvedVisibilities()
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select(
                'c.id as category_id',
                'IDENTITY(c.parentCategory) as parent_category_id',
                'cvr.visibility as resolved_visibility'
            )
            ->from('OroB2BCatalogBundle:Category', 'c')
            ->leftJoin(
                'OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved',
                'cvr',
                'WITH',
                'cvr.category = c'
            )
            ->getQuery()
            ->getScalarResult();
    }
}
