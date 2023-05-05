<?php

namespace Oro\Bundle\OrderBundle\EventListener\ORM;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;

/**
 * Update parent order payment status on sub-order status updates.
 */
class PaymentStatusListener
{
    private PaymentStatusManager $paymentStatusManager;
    private array $processedStatuses = [];

    public function __construct(
        PaymentStatusManager $paymentStatusManager
    ) {
        $this->paymentStatusManager = $paymentStatusManager;
    }

    public function preUpdate(PaymentStatus $paymentStatus, PreUpdateEventArgs $event): void
    {
        if (!empty($this->processedStatuses[$paymentStatus->getId()])) {
            return;
        }

        $this->processedStatuses[$paymentStatus->getId()] = true;
        if (!is_a($paymentStatus->getEntityClass(), Order::class, true)) {
            return;
        }

        $order = $event->getEntityManager()->find(
            $paymentStatus->getEntityClass(),
            $paymentStatus->getEntityIdentifier()
        );

        if (!$order->getParent()) {
            return;
        }

        $this->paymentStatusManager->updateStatusForEntity(
            $paymentStatus->getEntityClass(),
            $order->getParent()->getId()
        );
        $this->processedStatuses = [];
    }
}
