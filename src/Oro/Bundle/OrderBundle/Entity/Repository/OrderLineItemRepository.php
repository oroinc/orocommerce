<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

/**
 * Doctrine repository for OrderLineItem entity.
 */
class OrderLineItemRepository extends ServiceEntityRepository
{
    /**
     * Finds the order line item by ID with eagerly loaded relations.
     *
     * @param int $orderLineItemId ID of the order line item
     *
     * @return OrderLineItem|null
     */
    public function findOrderLineItemWithRelations(int $orderLineItemId): ?OrderLineItem
    {
        $qb = $this->createQueryBuilder('orderLineItem');
        $qb
            ->leftJoin('orderLineItem.product', 'product')
            ->addSelect('product')
            ->leftJoin('orderLineItem.productUnit', 'productUnit')
            ->addSelect('productUnit')
            ->leftJoin('orderLineItem.kitItemLineItems', 'kitItemLineItem')
            ->addSelect('kitItemLineItem')
            ->where($qb->expr()->eq('orderLineItem.id', ':orderLineItemId'))
            ->setParameter('orderLineItemId', $orderLineItemId, Types::INTEGER);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
