<?php

namespace Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository;

use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\ProductBundle\Entity\Product;

class CustomerProductVisibilityRepository extends AbstractProductVisibilityRepository
{
    const BATCH_SIZE = 1000;
    
    /**
     * Delete from CustomerProductVisibility visibilities with fallback to 'category' when category is absent
     */
    public function setToDefaultWithoutCategory()
    {
        $qb = $this->createQueryBuilder('customerProductVisibility');
        $qb->delete()
            ->where($qb->expr()->in('customerProductVisibility.id', ':customerProductVisibilityIds'));

        while ($customerProductVisibilityIds = $this->getVisibilityIdsForDelete()) {
            $qb->getQuery()->execute(['customerProductVisibilityIds' => $customerProductVisibilityIds]);
        }
    }

    /**
     * @param Product $product
     */
    public function setToDefaultWithoutCategoryByProduct(Product $product)
    {
        $qb = $this->createQueryBuilder('entity');
        $qb->delete()
            ->andWhere('entity.product = :product')
            ->andWhere('entity.visibility = :visibility')
            ->setParameter('product', $product)
            ->setParameter('visibility', CustomerProductVisibility::CATEGORY)
            ->getQuery()
            ->execute();
    }

    /**
     * @return int[]
     */
    protected function getVisibilityIdsForDelete()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $result = $qb->select('customerProductVisibility.id')
            ->from($this->getEntityName(), 'customerProductVisibility')
            ->leftJoin('customerProductVisibility.product', 'product')
            ->leftJoin(
                'OroCatalogBundle:Category',
                'category',
                Join::WITH,
                $qb->expr()->isMemberOf('product', 'category.products')
            )
            ->where($qb->expr()->isNull('category.id'))
            ->andWhere($qb->expr()->eq('customerProductVisibility.visibility', ':visibility'))
            ->setMaxResults(self::BATCH_SIZE)
            ->setParameter('visibility', CustomerProductVisibility::CATEGORY)
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $result);
    }
}
