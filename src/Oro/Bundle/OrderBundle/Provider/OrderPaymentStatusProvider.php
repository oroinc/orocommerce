<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;

/**
 * Add support of payment status calculation for order with sub-orders.
 */
class OrderPaymentStatusProvider extends PaymentStatusProvider
{
    public function getPaymentStatus($entity)
    {
        if ($entity instanceof Order && !$entity->getSubOrders()->isEmpty()) {
            $paymentTransactions = [];
            foreach ($entity->getSubOrders() as $subOrder) {
                $paymentTransactions[] = $this->paymentTransactionProvider->getPaymentTransactions($subOrder);
            }
            if ($paymentTransactions) {
                $paymentTransactions = array_merge(...$paymentTransactions);
            }
        } else {
            $paymentTransactions = $this->paymentTransactionProvider->getPaymentTransactions($entity);
        }

        return $this->getStatusByEntityAndTransactions($entity, new ArrayCollection($paymentTransactions));
    }
}
