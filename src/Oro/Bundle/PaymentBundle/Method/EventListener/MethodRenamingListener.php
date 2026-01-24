<?php

namespace Oro\Bundle\PaymentBundle\Method\EventListener;

use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentMethodConfigRepository;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;
use Oro\Bundle\PaymentBundle\Method\Event\MethodRenamingEvent;

/**
 * Handles payment method renaming events.
 *
 * This listener updates all references to a payment method when its identifier is renamed,
 * including payment method configurations in rules and payment transactions.
 */
class MethodRenamingListener
{
    /**
     * @var PaymentMethodConfigRepository
     */
    private $paymentMethodConfigRepository;

    /**
     * @var PaymentTransactionRepository
     */
    private $paymentTransactionRepository;

    public function __construct(
        PaymentMethodConfigRepository $paymentMethodConfigRepository,
        PaymentTransactionRepository $paymentTransactionRepository
    ) {
        $this->paymentMethodConfigRepository = $paymentMethodConfigRepository;
        $this->paymentTransactionRepository = $paymentTransactionRepository;
    }

    public function onMethodRename(MethodRenamingEvent $event)
    {
        $this->updateRuleConfigs($event->getOldMethodIdentifier(), $event->getNewMethodIdentifier());
        $this->updateTransactions($event->getOldMethodIdentifier(), $event->getNewMethodIdentifier());
    }

    /**
     * @param string $oldId
     * @param string $newId
     */
    private function updateRuleConfigs($oldId, $newId)
    {
        $configs = $this->paymentMethodConfigRepository->findByType($oldId);
        foreach ($configs as $config) {
            $config->setType($newId);
        }
    }

    /**
     * @param string $oldId
     * @param string $newId
     */
    private function updateTransactions($oldId, $newId)
    {
        $transactions = $this->paymentTransactionRepository->findByPaymentMethod($oldId);
        foreach ($transactions as $transaction) {
            $transaction->setPaymentMethod($newId);
        }
    }
}
