<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

class CategoryVisibilityRepository extends EntityRepository
{
    /**
     * @param null $accountId
     * @return array
     */
    public function getVisibilityToAll($accountId = null)
    {
        $qb = $this->createQueryBuilder('category');
        $qb->select('category.id as id, category.parentCategory as parent_category FROM OroB2BCatalogBundle:Category');

        $qb->leftJoin(
            'OroB2BAccountBundle:Visibility:CategoryVisibility',
            'categoryVisibility',
            Join::WITH,
            $qb->expr()->eq('categoryVisibility.category.id', 'category.id')
        );
        $qb->addSelect('categoryVisibility.visibility AS to_all');

        $qb->leftJoin(
            'OroB2BAccountBundle:Visibility:AccountCategoryVisibility',
            'accountCategoryVisibility',
            Join::WITH,
            $qb->expr()->eq('accountCategoryVisibility.category', 'category.id')
        );
        $qb->addSelect('accountCategoryVisibility.visibility AS to_account');

        $qb->leftJoin(
            'OroB2BAccountBundle:Visibility:AccountGroupCategoryVisibility',
            'accountGroupCategoryVisibility',
            Join::WITH,
            $qb->expr()->eq('accountGroupCategoryVisibility.category', 'category.id')
        );
        $qb->addSelect('accountGroupCategoryVisibility.visibility AS to_group');

        $qb->where('accountCategoryVisibility.account = :account')
        ->setParameter('account', $accountId);

        // parent categories should be first for optimized calculation because of some visibility dependency to parent
        $qb->orderBy('category.level', 'ASC');

        return $qb->getQuery()->getArrayResult();
    }
}
