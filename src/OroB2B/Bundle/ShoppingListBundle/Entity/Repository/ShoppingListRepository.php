<?php

namespace OroB2B\Bundle\ShoppingListBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\NonUniqueResultException;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListRepository extends EntityRepository
{
    /**
     * @param AccountUser $accountUser
     *
     * @return ShoppingList|null
     */
    public function findCurrentForAccountUser(AccountUser $accountUser)
    {
        return $this->createQueryBuilder('list')
            ->select('list')
            ->where('list.accountUser = :accountUser')
            ->andWhere('list.isCurrent = true')
            ->setParameter('accountUser', $accountUser)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * @param AccountUser $accountUser
     *
     * @return QueryBuilder
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
            ->setParameter('account', $accountUser->getAccount());
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
     * @throws NonUniqueResultException
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

    /**
     * @param AccountUser $accountUser
     *
     * @return array
     */
    public function findAllExceptCurrentForAccountUser(AccountUser $accountUser)
    {
        return $this->createQueryBuilder('list')
            ->select('list')
            ->where('list.accountUser = :accountUser')
            ->andWhere('list.isCurrent = false')
            ->setParameter('accountUser', $accountUser)
            ->getQuery()->getResult();
    }

    /**
     * @param AccountUser $accountUser
     *
     * @return ShoppingList|null
     */
    public function findLatestForAccountUserExceptCurrent(AccountUser $accountUser)
    {
        return $this->createQueryBuilder('list')
            ->select('list')
            ->where('list.accountUser = :accountUser')
            ->andWhere('list.isCurrent = false')
            ->setParameter('accountUser', $accountUser)
            ->orderBy('list.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }
}
