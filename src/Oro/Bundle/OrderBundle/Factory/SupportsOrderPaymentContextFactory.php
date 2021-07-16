<?php

namespace Oro\Bundle\OrderBundle\Factory;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Context\Factory\SupportsEntityPaymentContextFactoryInterface;

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

    /**
     * {@inheritDoc}
     */
    public function create($entityClass, $entityId)
    {
        if ($this->supports($entityClass, $entityId)) {
            $order = $this->findOrder($entityId);

            return $this->orderPaymentContextFactory->create($order);
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
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
