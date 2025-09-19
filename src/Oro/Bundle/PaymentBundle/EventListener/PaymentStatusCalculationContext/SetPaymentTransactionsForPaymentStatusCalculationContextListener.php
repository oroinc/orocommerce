<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\EventListener\PaymentStatusCalculationContext;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PaymentBundle\Event\PaymentStatusCalculationContextCollectEvent;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

/**
 * Collects payment transactions for the payment status calculation context.
 * This listener is triggered at the beginning of the payment status calculation process.
 */
final class SetPaymentTransactionsForPaymentStatusCalculationContextListener
{
    public function __construct(private readonly PaymentTransactionProvider $paymentTransactionProvider)
    {
    }

    public function onPaymentStatusCalculationContextCollect(PaymentStatusCalculationContextCollectEvent $event): void
    {
        if ($event->getContextItem('paymentTransactions') !== null) {
            return;
        }

        $event->setContextItem(
            'paymentTransactions',
            new ArrayCollection($this->paymentTransactionProvider->getPaymentTransactions($event->getEntity()))
        );
    }
}
