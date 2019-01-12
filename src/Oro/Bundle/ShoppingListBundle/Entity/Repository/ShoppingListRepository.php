<?php

namespace Oro\Bundle\ShoppingListBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

class ShoppingListRepository extends EntityRepository
{
    /**
     * @param AclHelper $aclHelper
     * @param bool $selectRelations
     * @param null|int $websiteId
     * @return null|ShoppingList
     */
    public function findAvailableForCustomerUser(AclHelper $aclHelper, $selectRelations = false, $websiteId = null)
    {
        /** @var ShoppingList $shoppingList */
        $qb = $this->getShoppingListQueryBuilder($selectRelations);
        $qb->addOrderBy('list.id', 'DESC')->setMaxResults(1);
        if ($websiteId) {
            $qb->andWhere($qb->expr()->eq('list.website', ':website'))->setParameter('website', $websiteId);
        }
        return $aclHelper->apply($qb)->getOneOrNullResult();
    }

    /**
     * @param AclHelper $aclHelper
     * @param array $sortCriteria
     * @param ShoppingList|int|null $excludeShoppingList
     * @param null|int $websiteId
     * @return array
     */
    public function findByUser(
        AclHelper $aclHelper,
        array $sortCriteria = [],
        $excludeShoppingList = null,
        $websiteId = null
    ) {
        $qb = $this->createQueryBuilder('list')
            ->select('list, items')
            ->leftJoin('list.lineItems', 'items');

        if ($excludeShoppingList) {
            $qb->andWhere($qb->expr()->neq('list.id', ':excludeShoppingList'))
                ->setParameter('excludeShoppingList', $excludeShoppingList);
        }

        if ($websiteId) {
            $qb->andWhere($qb->expr()->eq('list.website', ':website'))->setParameter('website', $websiteId);
        }

        foreach ($sortCriteria as $field => $sortOrder) {
            QueryBuilderUtil::checkField($field);
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
     * @param null|int $websiteId
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function findByUserAndId(AclHelper $aclHelper, $id, $websiteId = null)
    {
        $qb = $this->createQueryBuilder('list')
            ->select('list')
            ->andWhere('list.id = :id')
            ->setParameter('id', $id);

        if ($websiteId) {
            $qb->andWhere($qb->expr()->eq('list.website', ':website'))->setParameter('website', $websiteId);
        }

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

    /**
     * @param int $customerId
     * @param int $organizationId
     * @return integer
     */
    public function countUserShoppingLists($customerId, $organizationId)
    {
        $results = $this->createQueryBuilder('shopping_list')
            ->select('COUNT(shopping_list)')
            ->where('shopping_list.customerUser=:customerUser')
            ->andWhere('shopping_list.organization=:organization')
            ->setParameter('customerUser', $customerId)
            ->setParameter('organization', $organizationId)
            ->getQuery()
            ->getSingleScalarResult();

        return (integer) $results;
    }
}
