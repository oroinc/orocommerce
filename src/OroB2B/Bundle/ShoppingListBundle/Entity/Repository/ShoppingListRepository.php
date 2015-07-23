<?php

namespace OroB2B\Bundle\ShoppingListBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;

class ShoppingListRepository extends EntityRepository
{
    /**
     * @param AccountUser $accountUser
     *
     * @return array
     */
    public function findCurrentForAccountUser(AccountUser $accountUser)
    {
        return $this->createQueryBuilder('list')
            ->select('list')
            ->where('list.accountUser = :accountUser')
            ->andWhere('list.isCurrent = 1')
            ->setParameter('accountUser', $accountUser)
            ->getQuery()->getOneOrNullResult();
    }

    public function createFindForAccountUserQueryBuilder(AccountUser $accountUser)
    {
        $qb = $this->createQueryBuilder('sl');

        return $qb->where(
            $qb->expr()->orX(
                'sl.accountUser = :accountUser',
                'sl.account = :account'
            )
        )
        ->setParameter('accountUser', $accountUser)
        ->setParameter('account', $accountUser->getCustomer());
    }
}
