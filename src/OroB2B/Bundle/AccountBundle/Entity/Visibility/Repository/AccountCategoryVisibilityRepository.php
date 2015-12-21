<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Visibility\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use OroB2B\Bundle\AccountBundle\Entity\Account;

class AccountCategoryVisibilityRepository extends EntityRepository
{
    /**
     * @param Account $account
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getCategoryVisibilitiesForAccount(Account $account)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder
            ->select(
                'c.id as category_id',
                'IDENTITY(c.parentCategory) as category_parent_id',
                'categoryVisibility.visibility',
                'accountCategoryVisibility.visibility as account_visibility'
            )
            ->from('OroB2BCatalogBundle:Category', 'c')
            ->leftJoin(
                'OroB2BAccountBundle:Visibility\CategoryVisibility',
                'categoryVisibility',
                Join::WITH,
                $queryBuilder->expr()->eq('categoryVisibility.category', 'c')
            )
            ->leftJoin(
                'OroB2BAccountBundle:Visibility\AccountCategoryVisibility',
                'accountCategoryVisibility',
                Join::WITH,
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('accountCategoryVisibility.category', 'c'),
                    $queryBuilder->expr()->eq('accountCategoryVisibility.account', ':account')
                )
            )
            ->setParameter('account', $account)
            ->orderBy('c.level', 'ASC');

        if ($account->getGroup()) {
            $queryBuilder
                ->addSelect('accountGroupCategoryVisibility.visibility as account_group_visibility')
                ->leftJoin(
                    'OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility',
                    'accountGroupCategoryVisibility',
                    Join::WITH,
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq('accountGroupCategoryVisibility.category', 'c'),
                        $queryBuilder->expr()->eq('accountGroupCategoryVisibility.accountGroup', ':accountGroup')
                    )
                )
                ->setParameter('accountGroup', $account->getGroup());
        }

        return $queryBuilder;
    }
}
