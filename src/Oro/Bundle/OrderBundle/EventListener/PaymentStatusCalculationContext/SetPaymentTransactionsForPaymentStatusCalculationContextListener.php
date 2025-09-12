<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\EventListener\PaymentStatusCalculationContext;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Event\PaymentStatusCalculationContextCollectEvent;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

/**
 * Collects payment transactions for the payment status calculation context for orders with sub-orders.
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

        $entity = $event->getEntity();
        if ($entity instanceof Order && !$entity->getSubOrders()->isEmpty()) {
            $paymentTransactions = [];
            foreach ($entity->getSubOrders() as $subOrder) {
                $paymentTransactions[] = $this->paymentTransactionProvider->getPaymentTransactions($subOrder);
            }

            $event->setContextItem('paymentTransactions', new ArrayCollection(array_merge(...$paymentTransactions)));
        }
    }
}
