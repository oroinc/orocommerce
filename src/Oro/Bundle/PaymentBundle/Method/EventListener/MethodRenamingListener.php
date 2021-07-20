<?php

namespace Oro\Bundle\PaymentBundle\Method\EventListener;

use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentMethodConfigRepository;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;
use Oro\Bundle\PaymentBundle\Method\Event\MethodRenamingEvent;

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
