<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;

class AccountGroupProductVisibilityRepository extends EntityRepository
{
    /**
     * Return categories list of categories of products which has "category" fallback in AccountGroupProductVisibility
     *
     * @return array
     */
    public function getCategoriesByAccountGroupProductVisibility()
    {
        $result = $this->getEntityManager()
            ->getRepository('OroB2BCatalogBundle:Category')
            ->createQueryBuilder('category')
            ->select('partial category.{id}')
            ->innerJoin('category.products', 'product')
            ->innerJoin(
                'OroB2BAccountBundle:Visibility\AccountGroupProductVisibility',
                'agpv',
                Join::WITH,
                'agpv.product = product AND agpv.visibility = :category'
            )
            ->setParameter('category', AccountGroupProductVisibility::CATEGORY)
            ->getQuery()
            ->getResult();

        return $result;
    }
}
