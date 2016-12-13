<?php

namespace Oro\Bundle\ShoppingListBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListRepository extends EntityRepository
{

    /**
     * @param AclHelper $aclHelper
     * @param bool $selectRelations
     * @return null|ShoppingList
     */
    public function findAvailableForAccountUser(AclHelper $aclHelper, $selectRelations = false)
    {
        /** @var ShoppingList $shoppingList */
        $qb = $this->getShoppingListQueryBuilder($selectRelations);
        $qb->addOrderBy('list.id', 'DESC')
            ->setMaxResults(1);
        $shoppingList = $aclHelper->apply($qb)->getOneOrNullResult();

        return $shoppingList;
    }

    /**
     * @param AclHelper $aclHelper
     * @param array $sortCriteria
     * @param ShoppingList|int|null $excludeShoppingList
     *
     * @return array
     */
    public function findByUser(AclHelper $aclHelper, array $sortCriteria = [], $excludeShoppingList = null)
    {
        $qb = $this->createQueryBuilder('list')
            ->select('list, items')
            ->leftJoin('list.lineItems', 'items');

        if ($excludeShoppingList) {
            $qb->andWhere($qb->expr()->neq('list.id', ':excludeShoppingList'))
                ->setParameter('excludeShoppingList', $excludeShoppingList);
        }

        foreach ($sortCriteria as $field => $sortOrder) {
            if ($sortOrder === Criteria::ASC) {
                $qb->addOrderBy($qb->expr()->asc($field));
            } elseif ($sortOrder === Criteria::DESC) {
                $qb->addOrderBy($qb->expr()->desc($field));
            }
        }

        return $aclHelper->apply($qb, 'VIEW', false)->getResult();
    }

    /**
     * @param AclHelper $aclHelper
     * @param int $id
     *
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function findByUserAndId(AclHelper $aclHelper, $id)
    {
         $qb = $this->createQueryBuilder('list')
            ->select('list')
            ->andWhere('list.id = :id')
            ->setParameter('id', $id);

        return $aclHelper->apply($qb)->getOneOrNullResult();
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
            $this->modifyQbWithRelations($qb);
        }
        
        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     */
    protected function modifyQbWithRelations(QueryBuilder $qb)
    {
        $qb->addSelect('items', 'product', 'images', 'imageTypes', 'imageFile', 'unitPrecisions')
            ->leftJoin('list.lineItems', 'items')
            ->leftJoin('items.product', 'product')
            ->leftJoin('product.images', 'images')
            ->leftJoin('images.types', 'imageTypes')
            ->leftJoin('images.image', 'imageFile')
            ->leftJoin('product.unitPrecisions', 'unitPrecisions');
    }
}
