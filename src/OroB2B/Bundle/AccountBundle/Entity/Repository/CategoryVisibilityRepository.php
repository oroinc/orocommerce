<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use OroB2B\Bundle\AccountBundle\Entity\Account;

class CategoryVisibilityRepository extends EntityRepository
{
    /**
     * @param Account $account
     * @return array
     */
    public function getVisibilityToAll(Account $account)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('category.id as id, IDENTITY(category.parentCategory) as parent_category'); //,
        $qb->from('OroB2BCatalogBundle:Category', 'category');
        $qb->leftJoin(
            'OroB2BAccountBundle:Visibility\CategoryVisibility',
            'categoryVisibility',
            Join::WITH,
            'IDENTITY(categoryVisibility.category) = category.id'
        );
        $qb->addSelect('categoryVisibility.visibility AS to_all');
//
        $qb->leftJoin(
            'OroB2BAccountBundle:Visibility\AccountCategoryVisibility',
            'accountCategoryVisibility',
            Join::WITH,
            'IDENTITY(accountCategoryVisibility.category) = category.id '.
            'and accountCategoryVisibility.account = :account'
        );
        $qb->setParameter('account', $account);
        $qb->addSelect('accountCategoryVisibility.visibility AS to_account');

        $qb->leftJoin(
            'OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility',
            'accountGroupCategoryVisibility',
            Join::WITH,
            'IDENTITY(accountGroupCategoryVisibility.category) = category.id '.
            'and accountGroupCategoryVisibility.accountGroup = :accountGroup'
        );
        $qb->setParameter('accountGroup', $account->getGroup());
        $qb->addSelect('accountGroupCategoryVisibility.visibility AS to_group');

        // parent categories should be first for optimized calculation because of some visibility dependency to parent
        $qb->orderBy('category.level', 'ASC');

        return $qb->getQuery()->getArrayResult();
    }
}
