<?php

namespace Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;

/**
 * Repository for CategoryVisibility entity
 */
class CategoryVisibilityRepository extends AbstractCategoryVisibilityRepository
{
    /**
     * @return array [['category_id' => <int>, 'category_parent_id' => <int>, 'visibility' => <string>], ...]
     */
    public function getCategoriesVisibilities()
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder
            ->select(
                'c.id as category_id',
                'IDENTITY(c.parentCategory) as category_parent_id',
                'categoryVisibility.visibility'
            )
            ->from(Category::class, 'c')
            ->leftJoin(
                CategoryVisibility::class,
                'categoryVisibility',
                Join::WITH,
                $queryBuilder->expr()->eq('categoryVisibility.category', 'c')
            )
            ->addOrderBy('c.level', 'ASC')
            ->addOrderBy('c.left', 'ASC');

        return $queryBuilder->getQuery()->getScalarResult();
    }
}
