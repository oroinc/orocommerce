<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\TransactionCompleteEvent;
use Oro\Bundle\PaymentBundle\Webhook\PaymentStatusWebhookNotifier;

/**
 * Sends a webhook notification when an entity payment status is updated.
 */
abstract class AbstractPaymentStatusListener
{
    use FeatureCheckerHolderTrait;

    public const string PAYMENT_NOTIFICATION_SENT = 'payment_notification_sent';

    public function __construct(
        protected readonly PaymentStatusWebhookNotifier $paymentStatusWebhookNotifier,
        protected readonly ManagerRegistry $registry
    ) {
    }

    abstract protected function processTransactionComplete(PaymentTransaction $transaction, object $object): bool;

    public function onTransactionComplete(TransactionCompleteEvent $event): void
    {
        $paymentTransaction = $event->getTransaction();
        // Skip notified transactions
        if ($this->isTransactionProcessed($paymentTransaction)) {
            return;
        }

        // Do not notify about failed transactions
        if (!$paymentTransaction->isSuccessful()) {
            return;
        }
        // Skip ongoing active transactions
        if ($paymentTransaction->isActive()) {
            return;
        }

        $entityClass = $paymentTransaction->getEntityClass();
        $entityId = $paymentTransaction->getEntityIdentifier();

        $object = $this->registry->getRepository($entityClass)->find($entityId);

        if ($this->processTransactionComplete($paymentTransaction, $object)) {
            $this->markProcessed($paymentTransaction);
        }
    }

    protected function isTransactionProcessed(PaymentTransaction $transaction): bool
    {
        return (bool)$transaction->getTransactionOption(self::PAYMENT_NOTIFICATION_SENT);
    }

    protected function markProcessed(PaymentTransaction $paymentTransaction): void
    {
        $transactionOptions = $paymentTransaction->getTransactionOptions();
        $transactionOptions[self::PAYMENT_NOTIFICATION_SENT] = true;
        $paymentTransaction->setTransactionOptions($transactionOptions);
    }
}
