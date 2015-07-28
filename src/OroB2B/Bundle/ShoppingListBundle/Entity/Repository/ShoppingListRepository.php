<?php

namespace OroB2B\Bundle\ShoppingListBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

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

    /**
     * @param AccountUser $accountUser
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
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

    /**
     * @param AccountUser $accountUser
     *
     * @return array
     */
    public function findByUser(AccountUser $accountUser)
    {
        return $this->createQueryBuilder('list')
            ->select('list')
            ->where('list.accountUser = :accountUser')
            ->setParameter('accountUser', $accountUser)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param AccountUser $accountUser
     * @param int         $id
     *
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByUserAndId(AccountUser $accountUser, $id)
    {
        return $this->createQueryBuilder('list')
            ->select('list')
            ->where('list.accountUser = :accountUser')
            ->andWhere('list.id = :id')
            ->setParameter('accountUser', $accountUser)
            ->setParameter('id', $id)
            ->getQuery()->getOneOrNullResult();
    }
}
