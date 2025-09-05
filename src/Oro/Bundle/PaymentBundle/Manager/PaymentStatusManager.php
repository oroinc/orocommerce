<?php

namespace Oro\Bundle\PaymentBundle\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentStatusRepository;
use Oro\Bundle\PaymentBundle\Event\PaymentStatusUpdatedEvent;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProviderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Main entry point for managing payment statuses of entities.
 */
class PaymentStatusManager
{
    private ?EventDispatcherInterface $eventDispatcher = null;

    public function __construct(
        private PaymentStatusProviderInterface $statusProvider,
        private DoctrineHelper $doctrineHelper
    ) {
    }

    public function setEventDispatcher(?EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
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
            $paymentStatus = $this->statusProvider->getPaymentStatus($entity);
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
            $paymentStatus = $this->statusProvider->getPaymentStatus($entity);
            $paymentStatusEntity = $this->upsertPaymentStatus($entityClass, $entityId, $paymentStatus, $force);
        }

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
        $this->eventDispatcher?->dispatch($event);

        return $paymentStatusEntity;
    }

    /**
     * Will be deleted in 7.0, use updatePaymentStatus instead.
     */
    public function updateStatus(PaymentTransaction $transaction): void
    {
        $this->updateStatusForEntity(
            $transaction->getEntityClass(),
            $transaction->getEntityIdentifier()
        );
    }

    /**
     * Will be deleted in 7.0, use updatePaymentStatus instead.
     */
    public function updateStatusForEntity(string $entityClass, int $entityId): void
    {
        $paymentStatusEntity = $this->findPaymentStatus($entityClass, $entityId);
        if (!$paymentStatusEntity) {
            $paymentStatusEntity = $this->createPaymentStatus($entityClass, $entityId);
        }

        $entity = $this->doctrineHelper->getEntityReference($entityClass, $entityId);
        $status = $this->statusProvider->getPaymentStatus($entity);
        $paymentStatusEntity->setPaymentStatus($status);

        $em = $this->doctrineHelper->getEntityManagerForClass(PaymentStatus::class);
        $em->persist($paymentStatusEntity);
        $em->flush($paymentStatusEntity);
    }

    /**
     * Will be deleted in 7.0, use getPaymentStatus instead.
     */
    public function getPaymentStatusForEntity(string $entityClass, int $entityId): PaymentStatus
    {
        $paymentStatusEntity = $this->findPaymentStatus($entityClass, $entityId);
        if (!$paymentStatusEntity) {
            $entity = $this->doctrineHelper->getEntityReference($entityClass, $entityId);
            $status = $this->statusProvider->getPaymentStatus($entity);

            $paymentStatusEntity = $this->createPaymentStatus($entityClass, $entityId);
            $paymentStatusEntity->setPaymentStatus($status);
        }

        return $paymentStatusEntity;
    }

    private function createPaymentStatus(string $entityClass, int $entityId): PaymentStatus
    {
        $paymentStatusEntity = new PaymentStatus();
        $paymentStatusEntity->setEntityClass($entityClass);
        $paymentStatusEntity->setEntityIdentifier($entityId);

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
}
