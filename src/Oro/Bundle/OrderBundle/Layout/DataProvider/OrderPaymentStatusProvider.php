<?php

namespace Oro\Bundle\OrderBundle\Layout\DataProvider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;

class OrderPaymentStatusProvider
{
    /**
     * @var PaymentStatusProvider
     */
    protected $paymentStatusProvider;

    /**
     * @param PaymentStatusProvider $paymentStatusProvider
     */
    public function __construct(PaymentStatusProvider $paymentStatusProvider)
    {
        $this->paymentStatusProvider = $paymentStatusProvider;
    }

    /**
     * @param Order $order
     *
     * @return string
     */
    public function getPaymentStatus(Order $order)
    {
        return $this->paymentStatusProvider->getPaymentStatus($order);
    }
}
