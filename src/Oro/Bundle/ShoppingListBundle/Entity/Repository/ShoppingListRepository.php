<?php

namespace Oro\Bundle\ShoppingListBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

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
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->createQueryBuilder('list')
            ->where('list.accountUser = :accountUser')
            ->setParameter('accountUser', $accountUser)
            ->orderBy('list.current', 'DESC')
            ->addOrderBy('list.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($shoppingList && $selectRelations) {
            $this->findOneByIdWithRelations($shoppingList->getId());
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
     * @param AclHelper $aclHelper
     * @param array $sortCriteria
     *
     * @return array
     */
    public function findByUser(AclHelper $aclHelper, array $sortCriteria = [])
    {
        $qb = $this->createQueryBuilder('list')
            ->select('list, items')
            ->leftJoin('list.lineItems', 'items');

        $aclHelper->applyAclToQb(
            ShoppingList::class,
            $qb,
            'VIEW',
            ['accountUser' => 'list.accountUser', 'organization' => 'list.organization']
        );

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
