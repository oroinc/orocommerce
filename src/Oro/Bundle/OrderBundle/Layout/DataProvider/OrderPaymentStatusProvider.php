<?php

namespace Oro\Bundle\OrderBundle\Layout\DataProvider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;

/**
 * Layout data provider of an order payment status.
 */
class OrderPaymentStatusProvider
{
    public function __construct(private readonly PaymentStatusManager $paymentStatusManager)
    {
    }

    /**
     * @param Order $order
     *
     * @return string
     */
    public function getPaymentStatus(Order $order)
    {
        return (string) $this->paymentStatusManager->getPaymentStatus($order);
    }
}
