<?php

namespace Oro\Bundle\OrderBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

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
            ->where($qb->expr()->in('orders.currency', $removingCurrencies));
        if ($organization instanceof Organization) {
            $qb->andWhere('orders.organization = :organization');
            $qb->setParameter(':organization', $organization);
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
            ->where($qb->expr()->eq('orders.id', $id));

        return $qb->getQuery()->getOneOrNullResult();
    }
}
