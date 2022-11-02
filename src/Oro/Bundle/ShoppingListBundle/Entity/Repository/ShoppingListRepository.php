<?php

namespace Oro\Bundle\ShoppingListBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CustomerBundle\Entity\Repository\ResetCustomerUserTrait;
use Oro\Bundle\CustomerBundle\Entity\Repository\ResettableCustomerUserRepositoryInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * A repository for ShoppingList entities.
 */
class ShoppingListRepository extends ServiceEntityRepository implements ResettableCustomerUserRepositoryInterface
{
    use ResetCustomerUserTrait;

    /**
     * @param AclHelper $aclHelper
     * @param bool $selectRelations
     *
     * @return ShoppingList|null
     */
    public function findAvailableForCustomerUser(AclHelper $aclHelper, $selectRelations = false)
    {
        /** @var ShoppingList $shoppingList */
        $qb = $this->getShoppingListQueryBuilder($selectRelations);
        $qb->addOrderBy('list.id', 'DESC')->setMaxResults(1);

        return $aclHelper->apply($qb)->getOneOrNullResult();
    }

    /**
     * @param AclHelper $aclHelper
     * @param array $sortCriteria
     * @param ShoppingList|int|null $excludeShoppingList
     *
     * @return array
     */
    public function findByUser(
        AclHelper $aclHelper,
        array $sortCriteria = [],
        $excludeShoppingList = null
    ) {
        $qb = $this->createQueryBuilder('list')
            ->select('list');

        if ($excludeShoppingList) {
            $qb
                ->andWhere($qb->expr()->neq('list.id', ':excludeShoppingList'))
                ->setParameter('excludeShoppingList', $excludeShoppingList);
        }

        foreach ($sortCriteria as $field => $sortOrder) {
            QueryBuilderUtil::checkField($field);
            if ($sortOrder === Criteria::ASC) {
                $qb->addOrderBy($qb->expr()->asc($field));
            } elseif ($sortOrder === Criteria::DESC) {
                $qb->addOrderBy($qb->expr()->desc($field));
            }
        }

        return $aclHelper->apply($qb, BasicPermission::VIEW, [AclHelper::CHECK_RELATIONS => false])->getResult();
    }

    /**
     * @param int $customerUserId
     * @param AclHelper $aclHelper
     * @param array $sortCriteria
     * @param ShoppingList|int|null $excludeShoppingList
     *
     * @return array
     */
    public function findByCustomerUserId(
        int $customerUserId,
        AclHelper $aclHelper,
        array $sortCriteria = [],
        $excludeShoppingList = null
    ): array {
        $qb = $this->createQueryBuilder('list');
        $qb->select('list, items')
            ->leftJoin('list.lineItems', 'items')
            ->where($qb->expr()->eq('list.customerUser', ':customerUserId'))
            ->setParameter('customerUserId', $customerUserId);

        if ($excludeShoppingList) {
            $qb
                ->andWhere($qb->expr()->neq('list.id', ':excludeShoppingList'))
                ->setParameter('excludeShoppingList', $excludeShoppingList);
        }

        foreach ($sortCriteria as $field => $sortOrder) {
            QueryBuilderUtil::checkField($field);
            if ($sortOrder === Criteria::ASC) {
                $qb->addOrderBy($qb->expr()->asc($field));
            } elseif ($sortOrder === Criteria::DESC) {
                $qb->addOrderBy($qb->expr()->desc($field));
            }
        }

        return $aclHelper->apply($qb, BasicPermission::VIEW, [AclHelper::CHECK_RELATIONS => false])->getResult();
    }

    /**
     * @param AclHelper $aclHelper
     * @param int $id
     *
     * @return ShoppingList|null
     */
    public function findByUserAndId(AclHelper $aclHelper, $id)
    {
        $qb = $this->createQueryBuilder('list')
            ->select('list')
            ->andWhere('list.id = :id')
            ->setParameter('id', $id, Types::INTEGER);

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
     * @param $website int|Website
     * @return int
     */
    public function countUserShoppingLists($customerId, $organizationId, $website)
    {
        $results = $this->createQueryBuilder('shopping_list')
            ->select('COUNT(shopping_list)')
            ->where('shopping_list.customerUser=:customerUser')
            ->andWhere('shopping_list.organization=:organization')
            ->andWhere('shopping_list.website=:website')
            ->setParameter('customerUser', $customerId)
            ->setParameter('organization', $organizationId)
            ->setParameter('website', $website)
            ->getQuery()
            ->getSingleScalarResult();

        return (integer) $results;
    }

    public function hasEmptyConfigurableLineItems(ShoppingList $shoppingList): bool
    {
        $qb = $this->createQueryBuilder('shopping_list');

        return (bool) $qb
            ->select('1')
            ->innerJoin('shopping_list.lineItems', 'shopping_list_line_item')
            ->innerJoin('shopping_list_line_item.product', 'line_item_product')
            ->where($qb->expr()->eq('shopping_list', ':shopping_list'))
            ->setParameter('shopping_list', $shoppingList->getId())
            ->andWhere($qb->expr()->eq('line_item_product.type', $qb->expr()->literal(Product::TYPE_CONFIGURABLE)))
            ->setMaxResults(1)
            ->getQuery()
            ->getScalarResult();
    }
}
