<?php

namespace Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository;

use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\ProductBundle\Entity\Product;

class CustomerGroupProductVisibilityRepository extends AbstractProductVisibilityRepository
{
    const BATCH_SIZE = 1000;

    /**
     * Delete from CustomerGroupProductVisibility visibilities with fallback to 'category' when category is absent
     */
    public function setToDefaultWithoutCategory()
    {
        $qb = $this->createQueryBuilder('customerGroupProductVisibility');
        $qb->delete()
            ->where($qb->expr()->in('customerGroupProductVisibility.id', ':customerGroupProductVisibilityIds'));

        while ($customerGroupProductVisibilityIds = $this->getVisibilityIdsForDelete()) {
            $qb->getQuery()->execute(['customerGroupProductVisibilityIds' => $customerGroupProductVisibilityIds]);
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
            ->setParameter('visibility', CustomerGroupProductVisibility::CATEGORY)
            ->getQuery()
            ->execute();
    }

    /**
     * @return int[]
     */
    protected function getVisibilityIdsForDelete()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $customerGroupProductVisibilities = $qb
            ->select('customerGroupProductVisibility.id')
            ->from($this->getEntityName(), 'customerGroupProductVisibility')
            ->leftJoin('customerGroupProductVisibility.product', 'product')
            ->leftJoin(
                'OroCatalogBundle:Category',
                'category',
                Join::WITH,
                $qb->expr()->isMemberOf('product', 'category.products')
            )
            ->where($qb->expr()->isNull('category.id'))
            ->andWhere($qb->expr()->eq('customerGroupProductVisibility.visibility', ':visibility'))
            ->setMaxResults(self::BATCH_SIZE)
            ->setParameter('visibility', CustomerGroupProductVisibility::CATEGORY)
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $customerGroupProductVisibilities);
    }
}
