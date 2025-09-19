<?php

namespace Oro\Bundle\PaymentBundle\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentStatusRepository;
use Oro\Bundle\PaymentBundle\Event\PaymentStatusUpdatedEvent;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\PaymentStatusCalculatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Main entry point for managing payment statuses of entities.
 */
class PaymentStatusManager
{
    public function __construct(
        private readonly PaymentStatusCalculatorInterface $paymentStatusCalculator,
        private readonly DoctrineHelper $doctrineHelper,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * Returns the payment status for the given entity.
     * Calculates and creates a new PaymentStatus entity if it does not exist.
     */
    public function getPaymentStatus(object $entity): PaymentStatus
    {
        $entityClass = $this->doctrineHelper->getEntityClass($entity);
        $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);

        $paymentStatusEntity = $this->findPaymentStatus($entityClass, $entityId);
        if (!$paymentStatusEntity) {
            $paymentStatus = $this->paymentStatusCalculator->calculatePaymentStatus($entity);
            $paymentStatusEntity = $this->upsertPaymentStatus($entityClass, $entityId, $paymentStatus, false);
        }

        return $paymentStatusEntity;
    }

    /**
     * Sets the payment status for the given entity.
     * Creates a new PaymentStatus entity if it does not exist.
     *
     * @param object $entity Entity for which the payment status should be set.
     * @param string $paymentStatus The payment status to set.
     * @param bool $force If true, the payment status will be set forcefully,
     *  so it will not be recalculated in the future anymore.
     */
    public function setPaymentStatus(object $entity, string $paymentStatus, bool $force = false): PaymentStatus
    {
        $entityClass = $this->doctrineHelper->getEntityClass($entity);
        $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);

        return $this->upsertPaymentStatus($entityClass, $entityId, $paymentStatus, $force);
    }

    /**
     * Updates (recalculates) the payment status for the given entity.
     * Creates a new PaymentStatus entity if it does not exist.
     *
     * @param object $entity Entity for which the payment status should be updated.
     * @param bool $force If true, the payment status will be recalculated even if it is set forcefully before.
     */
    public function updatePaymentStatus(object $entity, bool $force = false): PaymentStatus
    {
        $entityClass = $this->doctrineHelper->getEntityClass($entity);
        $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);

        $paymentStatusEntity = $this->findPaymentStatus($entityClass, $entityId);

        if ($force || !$paymentStatusEntity?->isForced()) {
            $paymentStatus = $this->paymentStatusCalculator->calculatePaymentStatus($entity);
            $paymentStatusEntity = $this->upsertPaymentStatus($entityClass, $entityId, $paymentStatus, $force);
        }

        return $paymentStatusEntity;
    }

    private function findPaymentStatus(string $entityClass, int $entityId): ?PaymentStatus
    {
        /** @var PaymentStatus $paymentStatusEntity */
        $paymentStatusEntity = $this->doctrineHelper
            ->getEntityRepository(PaymentStatus::class)
            ->findOneBy(
                [
                    'entityClass' => $entityClass,
                    'entityIdentifier' => $entityId,
                ]
            );

        return $paymentStatusEntity;
    }

    private function upsertPaymentStatus(
        string $entityClass,
        int $entityId,
        string $paymentStatus,
        bool $force
    ): ?PaymentStatus {
        /** @var PaymentStatusRepository $paymentStatusRepository */
        $paymentStatusRepository = $this->doctrineHelper->getEntityRepository(PaymentStatus::class);
        $paymentStatusEntity = $paymentStatusRepository
            ->upsertPaymentStatus($entityClass, $entityId, $paymentStatus, $force);

        $event = new PaymentStatusUpdatedEvent(
            $paymentStatusEntity,
            $this->doctrineHelper->getEntityReference($entityClass, $entityId)
        );
        $this->eventDispatcher->dispatch($event);

        return $paymentStatusEntity;
    }
}
