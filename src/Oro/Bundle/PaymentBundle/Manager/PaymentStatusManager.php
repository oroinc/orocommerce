<?php

namespace Oro\Bundle\PaymentBundle\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProviderInterface;

/**
 * Contains methods for managing PaymentStatus entity.
 */
class PaymentStatusManager
{
    public function __construct(
        private PaymentStatusProviderInterface $statusProvider,
        private DoctrineHelper $doctrineHelper
    ) {
    }

    public function updateStatus(PaymentTransaction $transaction): void
    {
        $this->updateStatusForEntity(
            $transaction->getEntityClass(),
            $transaction->getEntityIdentifier()
        );
    }

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
