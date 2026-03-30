<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Component\DraftSession\Manager\DraftSessionOrmFilterManager;

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

    /**
     * Finds the order line item draft for the given order line item and draft session UUID.
     * Consider disabling order_draft ORM filter via {@link DraftSessionOrmFilterManager} before calling this method.
     *
     * @param int $orderLineItemId ID of the original order line item entity or order line item draft entity
     *  if it is a newly created order line item.
     * @param string $draftSessionUuid UUID of the draft session.
     *
     * @return OrderLineItem|null
     */
    public function findOrderLineItemDraftWithRelations(int $orderLineItemId, string $draftSessionUuid): ?OrderLineItem
    {
        if (!$draftSessionUuid) {
            return null;
        }

        $qb = $this->createQueryBuilder('orderLineItem');
        $qb
            ->leftJoin('orderLineItem.product', 'product')
            ->addSelect('product')
            ->leftJoin('orderLineItem.productUnit', 'productUnit')
            ->addSelect('productUnit')
            ->leftJoin('orderLineItem.kitItemLineItems', 'kitItemLineItem')
            ->addSelect('kitItemLineItem')
            ->where($qb->expr()->eq('orderLineItem.draftSessionUuid', ':draftSessionUuid'))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('orderLineItem.draftSource', ':orderLineItemId'),
                    $qb->expr()->eq('orderLineItem.id', ':orderLineItemId'),
                )
            )
            ->setParameter('orderLineItemId', $orderLineItemId, Types::INTEGER)
            ->setParameter('draftSessionUuid', $draftSessionUuid, Types::GUID);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Finds all order line item drafts for the given order and draft session UUID.
     * Consider disabling order_draft ORM filter via {@link DraftSessionOrmFilterManager} before calling this method.
     *
     * @param int $orderId ID of the original order entity or order draft entity if it is a newly created order.
     * @param string $draftSessionUuid UUID of the draft session.
     *
     * @return array<OrderLineItem>
     */
    public function findAllOrderLineItemDrafts(int $orderId, string $draftSessionUuid): array
    {
        $qb = $this->createQueryBuilder('orderLineItem');
        $qb
            ->innerJoin(
                'orderLineItem.orders',
                'orderLineItemOrder',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('orderLineItemOrder.draftSessionUuid', ':draftSessionUuid'),
                    $qb->expr()->orX(
                        $qb->expr()->eq('orderLineItemOrder.draftSource', ':orderId'),
                        $qb->expr()->eq('orderLineItemOrder.id', ':orderId')
                    )
                )
            )
            ->setParameter('orderId', $orderId, Types::INTEGER)
            ->setParameter('draftSessionUuid', $draftSessionUuid, Types::GUID);

        return $qb->getQuery()->getResult();
    }
}
