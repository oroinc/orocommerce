<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;

class AccountGroupProductVisibilityRepository extends EntityRepository
{
    /**
     * Delete from AccountGroupProductVisibility visibilities with fallback to 'category' when category is absent
     */
    public function setToDefaultValueProductAccountGroupProductVisibilityForProductsWithoutCategory()
    {
        $qb = $this->createQueryBuilder('accountGroupProductVisibility');

        $accountGroupProductVisibilities = $qb->select('accountGroupProductVisibility.id')
            ->leftJoin('accountGroupProductVisibility.product', 'product')
            ->leftJoin(
                'OroB2BCatalogBundle:Category',
                'category',
                Join::WITH,
                'product MEMBER OF category.products'
            )
            ->where($qb->expr()->isNull('category.id'))
            ->andWhere($qb->expr()->eq('accountGroupProductVisibility.visibility', ':visibility'))
            ->setParameter('visibility', AccountGroupProductVisibility::CATEGORY)
            ->getQuery()
            ->getScalarResult();

        if (!empty($accountGroupProductVisibilities)) {
            $accountGroupProductVisibilityIds = array_map('current', $accountGroupProductVisibilities);

            $qb = $this->createQueryBuilder('accountGroupProductVisibility');
            $qb->delete()
                ->where($qb->expr()->in('accountGroupProductVisibility.id', $accountGroupProductVisibilityIds))
                ->getQuery()
                ->execute();
        }
    }
}
