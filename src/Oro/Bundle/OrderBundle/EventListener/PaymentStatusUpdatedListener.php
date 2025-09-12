<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Event\PaymentStatusUpdatedEvent;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;

/**
 * Update parent order payment status on sub-order status updates.
 */
final class PaymentStatusUpdatedListener
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly PaymentStatusManager $paymentStatusManager
    ) {
    }

    public function onPaymentStatusUpdated(PaymentStatusUpdatedEvent $event): void
    {
        if (!$event->getTargetEntity() instanceof Order) {
            return;
        }

        $paymentStatus = $event->getPaymentStatus();
        $entityManager = $this->doctrine->getManagerForClass(Order::class);

        $parentOrder = $entityManager->getRepository(Order::class)
            ->findParentOrder($paymentStatus->getEntityIdentifier());

        if (!$parentOrder) {
            return;
        }

        $this->paymentStatusManager->updatePaymentStatus($parentOrder);
    }
}
