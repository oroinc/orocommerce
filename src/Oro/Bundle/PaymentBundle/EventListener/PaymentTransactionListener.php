<?php

namespace Oro\Bundle\PaymentBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PaymentBundle\Event\TransactionCompleteEvent;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;

/**
 * Updates the payment status of an entity when a payment transaction is completed.
 */
final class PaymentTransactionListener
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly PaymentStatusManager $paymentStatusManager
    ) {
    }

    public function onTransactionComplete(TransactionCompleteEvent $event): void
    {
        $paymentTransaction = $event->getTransaction();

        $entityClass = $paymentTransaction->getEntityClass();
        $entityId = $paymentTransaction->getEntityIdentifier();

        /** @var EntityManager $entityManager */
        $entityManager = $this->doctrine->getManagerForClass($entityClass);
        $entity = $entityManager->getReference($entityClass, $entityId);

        $this->paymentStatusManager->updatePaymentStatus($entity);
    }
}
