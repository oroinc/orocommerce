<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class AccountProductVisibilityRepository extends EntityRepository
{
    /**
     * Return categories list of products which has "category" fallback in AccountProductVisibility
     *
     * @return Category[]
     */
    public function getCategoriesByAccountProductVisibility()
    {
        $result = $this->getEntityManager()
            ->getRepository('OroB2BCatalogBundle:Category')
            ->createQueryBuilder('category')
            ->select('partial category.{id}')
            ->distinct()
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

    /**
     * @return Account[]
     */
    public function getAccountsForCategoryType()
    {
        return $this->getEntityManager()
            ->getRepository('OroB2BAccountBundle:Account')
            ->createQueryBuilder('account')
            ->select('partial account.{id}')
            ->distinct()
            ->innerJoin(
                'OroB2BAccountBundle:Visibility\AccountProductVisibility',
                'apv',
                Join::WITH,
                'apv.account = account AND apv.visibility = :category'
            )
            ->where('apv.visibility = :category')
            ->setParameter('category', AccountProductVisibility::CATEGORY)
            ->getQuery()
            ->getResult();
    }
}
