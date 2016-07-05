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
     * @param bool        $selectRelations
     *
     * @return null|ShoppingList
     * @throws NonUniqueResultException
     */
    public function findCurrentForAccountUser(AccountUser $accountUser, $selectRelations = false)
    {
        $qb = $this->getShoppingListQueryBuilder($selectRelations);
        $qb->where('list.accountUser = :accountUser')
            ->andWhere('list.current = true')
            ->setParameter('accountUser', $accountUser)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param AccountUser $accountUser
     * @param bool        $selectRelations
     *
     * @return null|ShoppingList
     * @throws NonUniqueResultException
     */
    public function findOneForAccountUser(AccountUser $accountUser, $selectRelations = false)
    {
        $qb = $this->getShoppingListQueryBuilder($selectRelations)
            ->where('list.accountUser = :accountUser')
            ->setParameter('accountUser', $accountUser)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param AccountUser $accountUser
     * @param bool        $selectRelations
     *
     * @return null|ShoppingList
     */
    public function findAvailableForAccountUser(AccountUser $accountUser, $selectRelations = false)
    {
        $shoppingList = $this->findCurrentForAccountUser($accountUser, $selectRelations);

        if (!$shoppingList) {
            $shoppingList = $this->findOneForAccountUser($accountUser, $selectRelations);
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
            ->select('list, partial items.{id}')
            ->leftJoin('list.lineItems', 'items')
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
     * @return ShoppingList|null
     */
    public function findOneByIdWithRelations($id)
    {
        $qb = $this->getShoppingListQueryBuilder(true);
        $qb->where('list.id = :id')
            ->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param bool $selectRelations
     *
     * @return QueryBuilder
     */
    protected function getShoppingListQueryBuilder($selectRelations = false)
    {
        $qb = $this->createQueryBuilder('list')
            ->select('list');
        if ($selectRelations) {
            $qb->addSelect('items', 'product', 'images', 'imageTypes', 'imageFile', 'unitPrecisions')
                ->leftJoin('list.lineItems', 'items')
                ->leftJoin('items.product', 'product')
                ->leftJoin('product.images', 'images')
                ->leftJoin('images.types', 'imageTypes')
                ->leftJoin('images.image', 'imageFile')
                ->leftJoin('product.unitPrecisions', 'unitPrecisions');
        }
        
        return $qb;
    }
}
