<?php

namespace Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;

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
            ->where($qb->expr()->isNull('product.category'))
            ->andWhere($qb->expr()->eq('customerProductVisibility.visibility', ':visibility'))
            ->setMaxResults(self::BATCH_SIZE)
            ->setParameter('visibility', CustomerProductVisibility::CATEGORY)
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $result);
    }
}
