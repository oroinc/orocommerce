<?php

namespace OroB2B\Bundle\ShoppingListBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Common\Collections\Criteria;

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
            ->andWhere('list.current = true')
            ->setParameter('accountUser', $accountUser)
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * @param AccountUser $accountUser
     *
     * @return ShoppingList|null
     */
    public function findOneForAccountUser(AccountUser $accountUser)
    {
        return $this->createQueryBuilder('list')
            ->select('list')
            ->where('list.accountUser = :accountUser')
            ->setParameter('accountUser', $accountUser)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param AccountUser $accountUser
     *
     * @return null|ShoppingList
     */
    public function findAvailableForAccountUser(AccountUser $accountUser)
    {
        $shoppingList = $this->findCurrentForAccountUser($accountUser);

        if (!$shoppingList) {
            $shoppingList = $this->findOneForAccountUser($accountUser);
        }

        return $shoppingList;
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
     * @param array $sortCriteria
     *
     * @return array
     */
    public function findByUser(AccountUser $accountUser, array $sortCriteria = [])
    {
        $qb = $this->createQueryBuilder('list')
            ->select('list')
            ->where('list.accountUser = :accountUser')
            ->setParameter('accountUser', $accountUser);

        foreach ($sortCriteria as $field => $sortOrder) {
            if ($sortOrder === Criteria::ASC) {
                $qb->addOrderBy($qb->expr()->asc($field));
            } elseif ($sortOrder === Criteria::DESC) {
                $qb->addOrderBy($qb->expr()->desc($field));
            }
        }

        return $qb->getQuery()->getResult();
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
            ->andWhere('list.current = false')
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
        $qb = $this->createQueryBuilder('list');

        return $qb
            ->select('list')
            ->where('list.accountUser = :accountUser')
            ->andWhere('list.current = false')
            ->setParameter('accountUser', $accountUser)
            ->orderBy($qb->expr()->desc('list.id'))
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * @param int $id
     * @return ShoppingList
     */
    public function findWithRelatedObjectsById($id)
    {
        return $this->createQueryBuilder('list')
            ->select('list', 'items', 'product', 'images', 'imageTypes', 'imageFile', 'unitPrecisions')
            ->leftJoin('list.lineItems', 'items')
            ->leftJoin('items.product', 'product')
            ->leftJoin('product.images', 'images')
            ->leftJoin('images.types', 'imageTypes')
            ->leftJoin('images.image', 'imageFile')
            ->leftJoin('product.unitPrecisions', 'unitPrecisions')
            ->where('list.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
