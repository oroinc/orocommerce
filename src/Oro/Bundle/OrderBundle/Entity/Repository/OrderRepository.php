<?php

namespace Oro\Bundle\OrderBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Doctrine repository for Order entity
 */
class OrderRepository extends EntityRepository
{
    /**
     * @param array             $removingCurrencies
     * @param Organization|null $organization
     *
     * @return bool
     */
    public function hasRecordsWithRemovingCurrencies(
        array $removingCurrencies,
        Organization $organization = null
    ) {
        $qb = $this->createQueryBuilder('orders');
        $qb
            ->select('COUNT(orders.id)')
            ->where($qb->expr()->in('orders.currency', ':removingCurrencies'))
            ->setParameter('removingCurrencies', $removingCurrencies);

        if ($organization instanceof Organization) {
            $qb
                ->andWhere(($qb->expr()->in('orders.organization', ':organization')))
                ->setParameter('organization', $organization);
        }

        return (bool) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int $id
     * @return Order|null
     */
    public function getOrderWithRelations($id)
    {
        $qb = $this->createQueryBuilder('orders');
        $qb->select('orders, lineItems, shippingAddress, billingAddress, discounts')
            ->leftJoin('orders.lineItems', 'lineItems')
            ->leftJoin('orders.shippingAddress', 'shippingAddress')
            ->leftJoin('orders.billingAddress', 'billingAddress')
            ->leftJoin('orders.discounts', 'discounts')
            ->where($qb->expr()->eq('orders.id', ':orderId'))
            ->setParameter('orderId', $id)
            ->addOrderBy($qb->expr()->asc('orders.id'))
            ->addOrderBy($qb->expr()->asc('lineItems.id'));

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param array $productIds
     * @param int   $websiteId
     * @param array $orderStatuses
     *
     * @return QueryBuilder
     */
    public function getLatestOrderedProductsInfo(array $productIds, $websiteId, $orderStatuses)
    {
        $qb = $this->createQueryBuilder('orders');
        $qb->select('IDENTITY(orders.customerUser) as customer_user_id')
            ->addSelect('IDENTITY(lineItems.product) as product_id')
            ->addSelect(
                $qb->expr()->max('orders.createdAt') . ' as created_at'
            )
            ->innerJoin('orders.lineItems', 'lineItems')
            ->andWhere($qb->expr()->in('lineItems.product', ':productIdList'))
            ->andWhere($qb->expr()->eq('orders.website', ':websiteId'))
            ->andWhere($qb->expr()->in('orders.internal_status', ':orderStatuses'))
            ->groupBy('orders.customerUser, lineItems.product')
            ->orderBy('lineItems.product');

        $qb->setParameter('productIdList', $productIds)
            ->setParameter('orderStatuses', $orderStatuses)
            ->setParameter('websiteId', $websiteId);

        return $qb;
    }
}
