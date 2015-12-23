<?php

namespace OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryRepository extends EntityRepository
{
    /**
     * @param Category $category
     * @return bool
     */
    public function isCategoryVisible(Category $category)
    {
        $qb = $this->createQueryBuilder('categoryVisibilityResolved');
        $categoryVisibilityResolved = $qb->select('categoryVisibilityResolved.visibility')
            ->where($qb->expr()->eq('categoryVisibilityResolved.category', ':category'))
            ->setParameter('category', $category)
            ->getQuery()
            ->getOneOrNullResult();

        return isset($categoryVisibilityResolved['visibility'])
            && $categoryVisibilityResolved['visibility'] === BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE;
    }

    /**
     * @param int $visibility
     * @return array
     */
    public function getCategoryIdsByVisibility($visibility)
    {
        $qb = $this->createQueryBuilder('categoryVisibilityResolved');
        $categoryVisibilityResolved = $qb->select('IDENTITY(categoryVisibilityResolved.category)')
            ->where($qb->expr()->eq('categoryVisibilityResolved.visibility', $visibility))
            ->getQuery()
            ->getArrayResult();

        return array_map('current', $categoryVisibilityResolved);
    }
}
