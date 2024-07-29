<?php

namespace Oro\Bundle\PaymentBundle\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

/**
 * Contains methods for managing PaymentStatus entity.
 */
class PaymentStatusManager
{
    /** @var PaymentStatusProviderInterface */
    protected $statusProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var PaymentTransactionProvider */
    protected $paymentTransactionProvider;

    public function __construct(
        PaymentStatusProviderInterface $provider,
        DoctrineHelper $doctrineHelper,
        PaymentTransactionProvider $transactionProvider
    ) {
        $this->statusProvider = $provider;
        $this->doctrineHelper = $doctrineHelper;
        $this->paymentTransactionProvider = $transactionProvider;
    }

    public function updateStatus(PaymentTransaction $transaction)
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
