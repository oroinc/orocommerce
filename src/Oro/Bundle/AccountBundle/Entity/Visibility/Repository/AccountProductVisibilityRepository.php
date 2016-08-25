<?php

namespace Oro\Bundle\AccountBundle\Entity\Visibility\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\ProductBundle\Entity\Product;

class AccountProductVisibilityRepository extends EntityRepository
{
    const BATCH_SIZE = 1000;
    
    /**
     * Delete from AccountProductVisibility visibilities with fallback to 'category' when category is absent
     */
    public function setToDefaultWithoutCategory()
    {
        $qb = $this->createQueryBuilder('accountProductVisibility');
        $qb->delete()
            ->where($qb->expr()->in('accountProductVisibility.id', ':accountProductVisibilityIds'));

        while ($accountProductVisibilityIds = $this->getVisibilityIdsForDelete()) {
            $qb->getQuery()->execute(['accountProductVisibilityIds' => $accountProductVisibilityIds]);
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
            ->setParameter('visibility', AccountProductVisibility::CATEGORY)
            ->getQuery()
            ->execute();
    }

    /**
     * @return int[]
     */
    protected function getVisibilityIdsForDelete()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $result = $qb->select('accountProductVisibility.id')
            ->from($this->getEntityName(), 'accountProductVisibility')
            ->leftJoin('accountProductVisibility.product', 'product')
            ->leftJoin(
                'OroCatalogBundle:Category',
                'category',
                Join::WITH,
                $qb->expr()->isMemberOf('product', 'category.products')
            )
            ->where($qb->expr()->isNull('category.id'))
            ->andWhere($qb->expr()->eq('accountProductVisibility.visibility', ':visibility'))
            ->setMaxResults(self::BATCH_SIZE)
            ->setParameter('visibility', AccountProductVisibility::CATEGORY)
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $result);
    }
}
