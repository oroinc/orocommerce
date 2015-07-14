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
     * @return array
     */
    public function findAllExceptCurrentForAccountUser(AccountUser $accountUser)
    {
        return $this->createQueryBuilder('list')
            ->select('list')
            ->where('list.accountUser = :accountUser')
            ->andWhere('list.isCurrent = 0')
            ->setParameter('accountUser', $accountUser)
            ->getQuery()->getResult();
    }
}
