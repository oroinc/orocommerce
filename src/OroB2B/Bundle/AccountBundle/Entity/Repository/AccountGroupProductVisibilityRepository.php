<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class AccountGroupProductVisibilityRepository extends EntityRepository
{
    /**
     * Return categories list of categories of products which has "category" fallback in AccountGroupProductVisibility
     *
     * @return Category[]
     */
    public function getCategoriesByAccountGroupProductVisibility()
    {
        $result = $this->getEntityManager()
            ->getRepository('OroB2BCatalogBundle:Category')
            ->createQueryBuilder('category')
            ->select('partial category.{id}')
            ->distinct()
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

    /**
     * @return AccountGroup[]
     */
    public function getAccountsGroupsForCategoryType()
    {
        return $this->getEntityManager()
            ->getRepository('OroB2BAccountBundle:AccountGroup')
            ->createQueryBuilder('accountGroup')
            ->select('partial accountGroup.{id}')
            ->distinct()
            ->innerJoin(
                'OroB2BAccountBundle:Visibility\AccountGroupProductVisibility',
                'agpv',
                Join::WITH,
                'agpv.accountGroup = accountGroup AND agpv.visibility = :category'
            )
            ->where('agpv.visibility = :category')
            ->setParameter('category', AccountGroupProductVisibility::CATEGORY)
            ->getQuery()
            ->getResult();
    }
}
