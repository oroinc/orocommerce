<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Visibility\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class AccountGroupProductVisibilityRepository extends EntityRepository
{
    const BATCH_SIZE = 1000;

    /**
     * Delete from AccountGroupProductVisibility visibilities with fallback to 'category' when category is absent
     */
    public function setToDefaultWithoutCategory()
    {
        $qb = $this->createQueryBuilder('accountGroupProductVisibility');
        $qb->delete()
            ->where($qb->expr()->in('accountGroupProductVisibility.id', ':accountGroupProductVisibilityIds'));

        while ($accountGroupProductVisibilityIds = $this->getVisibilityIdsForDelete()) {
            $qb->getQuery()->execute(['accountGroupProductVisibilityIds' => $accountGroupProductVisibilityIds]);
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
            ->setParameter('visibility', AccountGroupProductVisibility::CATEGORY)
            ->getQuery()
            ->execute();
    }

    /**
     * @return int[]
     */
    protected function getVisibilityIdsForDelete()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $accountGroupProductVisibilities = $qb
            ->select('accountGroupProductVisibility.id')
            ->from($this->getEntityName(), 'accountGroupProductVisibility')
            ->leftJoin('accountGroupProductVisibility.product', 'product')
            ->leftJoin(
                'OroB2BCatalogBundle:Category',
                'category',
                Join::WITH,
                $qb->expr()->isMemberOf('product', 'category.products')
            )
            ->where($qb->expr()->isNull('category.id'))
            ->andWhere($qb->expr()->eq('accountGroupProductVisibility.visibility', ':visibility'))
            ->setMaxResults(self::BATCH_SIZE)
            ->setParameter('visibility', AccountGroupProductVisibility::CATEGORY)
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $accountGroupProductVisibilities);
    }
}
