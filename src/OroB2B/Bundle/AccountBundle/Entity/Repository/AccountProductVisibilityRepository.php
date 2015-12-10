<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;

class AccountProductVisibilityRepository extends EntityRepository
{
    /**
     * Delete from AccountProductVisibility visibilities with fallback to 'category' when category is absent
     */
    public function setToDefaultValueAccountProductVisibilityForProductsWithoutCategory()
    {
        $qb = $this->createQueryBuilder('accountProductVisibility');

        $accountProductVisibilities = $qb->select('accountProductVisibility.id')
            ->leftJoin('accountProductVisibility.product', 'product')
            ->leftJoin(
                'OroB2BCatalogBundle:Category',
                'category',
                Join::WITH,
                'product MEMBER OF category.products'
            )
            ->where($qb->expr()->isNull('category.id'))
            ->andWhere($qb->expr()->eq('accountProductVisibility.visibility', ':visibility'))
            ->setParameter('visibility', AccountProductVisibility::CATEGORY)
            ->getQuery()
            ->getScalarResult();

        if (!empty($accountProductVisibilities)) {
            $accountProductVisibilityIds = array_map('current', $accountProductVisibilities);

            $qb = $this->createQueryBuilder('accountProductVisibility');
            $qb->delete()
                ->where($qb->expr()->in('accountProductVisibility.id', $accountProductVisibilityIds))
                ->getQuery()
                ->execute();
        }
    }
}
