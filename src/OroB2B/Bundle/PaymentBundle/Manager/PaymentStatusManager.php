<?php

namespace OroB2B\Bundle\PaymentBundle\Manager;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentStatus;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentStatusProvider;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

class PaymentStatusManager
{
    /** @var PaymentStatusProvider */
    protected $statusProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var PaymentTransactionProvider */
    protected $paymentTransactionProvider;

    /**
     * @param PaymentStatusProvider $provider
     * @param DoctrineHelper $doctrineHelper
     * @param PaymentTransactionProvider $transactionProvider
     */
    public function __construct(
        PaymentStatusProvider $provider,
        DoctrineHelper $doctrineHelper,
        PaymentTransactionProvider $transactionProvider
    ) {
        $this->statusProvider = $provider;
        $this->doctrineHelper = $doctrineHelper;
        $this->paymentTransactionProvider = $transactionProvider;
    }

    /**
     * @param PaymentTransaction $transaction
     */
    public function updateStatus(PaymentTransaction $transaction)
    {
        $entityClass = $transaction->getEntityClass();
        $entityId = $transaction->getEntityIdentifier();
        $object = $this->doctrineHelper->getEntityReference($entityClass, $entityId);
        $paymentStatusEntity = $this->doctrineHelper->getEntityRepository(PaymentStatus::class)->findOneBy([
            'entityClass' => $entityClass,
            'entityIdentifier' => $entityId
        ]);

        $transactions = new ArrayCollection([$transaction]);
        $status = $this->statusProvider->computeStatus($object, $transactions);

        if (!$paymentStatusEntity) {
            $paymentStatusEntity = new PaymentStatus();
            $paymentStatusEntity->setEntityClass($entityClass);
            $paymentStatusEntity->setEntityIdentifier($entityId);
        } elseif ($status !== PaymentStatusProvider::FULL) {
            $transactions = new ArrayCollection($this->paymentTransactionProvider->getPaymentTransactions($object));
            if (!$transactions->contains($transaction)) {
                $transactions->add($transaction);
            }
            $status = $this->statusProvider->computeStatus($object, $transactions);
        }
        $paymentStatusEntity->setPaymentStatus($status);
        $em = $this->doctrineHelper->getEntityManager(PaymentStatus::class);
        $em->persist($paymentStatusEntity);
    }
}
