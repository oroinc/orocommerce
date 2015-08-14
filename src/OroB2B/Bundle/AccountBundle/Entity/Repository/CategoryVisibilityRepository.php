<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;

use OroB2B\Bundle\AccountBundle\Entity\CategoryVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryVisibilityRepository extends EntityRepository
{
    /**
     * @param Category $category
     *
     * @return CategoryVisibility|null
     * @throws NonUniqueResultException
     */
    public function findOneByCategory(Category $category)
    {
        return $this->createQueryBuilder('cv')
            ->where('cv.category = :category')
            ->setParameter('category', $category)
            ->getQuery()->getOneOrNullResult();
    }
}
