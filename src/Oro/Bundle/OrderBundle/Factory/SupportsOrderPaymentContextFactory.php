<?php

namespace Oro\Bundle\OrderBundle\Factory;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Context\Factory\SupportsEntityPaymentContextFactoryInterface;

/**
 * Creates payment context for orders with caching support.
 *
 * Implements the payment context factory interface to create payment contexts
 * from Order entities. Caches loaded orders to optimize performance when
 * creating multiple payment contexts for the same orders.
 */
class SupportsOrderPaymentContextFactory implements SupportsEntityPaymentContextFactoryInterface
{
    /**
     * @var Order[]
     */
    private $orders = [];

    /**
     * @var DoctrineHelper
     */
    private $doctrine;

    /**
     * @var OrderPaymentContextFactory
     */
    private $orderPaymentContextFactory;

    public function __construct(DoctrineHelper $doctrine, OrderPaymentContextFactory $orderPaymentContextFactory)
    {
        $this->doctrine = $doctrine;
        $this->orderPaymentContextFactory = $orderPaymentContextFactory;
    }

    #[\Override]
    public function create($entityClass, $entityId)
    {
        if ($this->supports($entityClass, $entityId)) {
            $order = $this->findOrder($entityId);

            return $this->orderPaymentContextFactory->create($order);
        }

        return null;
    }

    #[\Override]
    public function supports($entityClass, $entityId)
    {
        if ($entityClass === Order::class) {
            return (bool) $this->findOrder($entityId);
        }

        return false;
    }

    /**
     * @param int $entityId
     *
     * @return Order|null
     */
    private function findOrder($entityId)
    {
        if (!array_key_exists($entityId, $this->orders)) {
            $this->orders[$entityId] = $this->doctrine->getEntity(Order::class, $entityId);
        }

        return $this->orders[$entityId];
    }
}
