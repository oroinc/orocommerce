<?php

namespace OroB2B\Bundle\CatalogBundle\Entity\Repository;

use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryRepository extends NestedTreeRepository
{
    /**
     * @return Category
     */
    public function getMasterCatalogRoot()
    {
        return $this->createQueryBuilder('category')
            ->andWhere('category.parentCategory IS NULL')
            ->orderBy('category.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();
    }
}
