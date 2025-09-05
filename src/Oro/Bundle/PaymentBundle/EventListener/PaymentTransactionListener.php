<?php

namespace Oro\Bundle\PaymentBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PaymentBundle\Event\TransactionCompleteEvent;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;

/**
 * Updates the payment status of an entity when a payment transaction is completed.
 */
class PaymentTransactionListener
{
    private ?ManagerRegistry $doctrine = null;

    public function __construct(
        private PaymentStatusManager $manager
    ) {
    }

    public function setDoctrine(?ManagerRegistry $doctrine): void
    {
        $this->doctrine = $doctrine;
    }

    public function onTransactionComplete(TransactionCompleteEvent $event): void
    {
        // BC layer.
        if (!$this->doctrine) {
            $this->manager->updateStatus($event->getTransaction());

            return;
        }

        $paymentTransaction = $event->getTransaction();

        $entityClass = $paymentTransaction->getEntityClass();
        $entityId = $paymentTransaction->getEntityIdentifier();

        /** @var EntityManager $entityManager */
        $entityManager = $this->doctrine->getManagerForClass($entityClass);
        $entity = $entityManager->getReference($entityClass, $entityId);

        $this->manager->updatePaymentStatus($entity);
    }
}
