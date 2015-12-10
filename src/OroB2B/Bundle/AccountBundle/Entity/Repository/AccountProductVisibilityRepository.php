<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;

class AccountProductVisibilityRepository extends EntityRepository
{
    /**
     * Return categories list of products which has "category" fallback in AccountProductVisibility
     *
     * @return array
     */
    public function getCategoriesByAccountProductVisibility()
    {
        $result = $this->getEntityManager()
            ->getRepository('OroB2BCatalogBundle:Category')
            ->createQueryBuilder('category')
            ->select('partial category.{id}')
            ->innerJoin('category.products', 'product')
            ->innerJoin(
                'OroB2BAccountBundle:Visibility\AccountProductVisibility',
                'apv',
                Join::WITH,
                'apv.product = product AND apv.visibility = :category'
            )
            ->setParameter('category', AccountProductVisibility::CATEGORY)
            ->getQuery()
            ->getResult();

        return $result;
    }
}
