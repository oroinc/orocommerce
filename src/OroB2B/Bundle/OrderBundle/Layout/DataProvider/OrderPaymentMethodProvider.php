<?php

namespace OroB2B\Bundle\OrderBundle\Layout\DataProvider;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

class OrderPaymentMethodProvider
{
    /**
     * @var PaymentTransactionProvider
     */
    protected $paymentTransactionProvider;

    /**
     * @param PaymentTransactionProvider $paymentTransactionProvider
     */
    public function __construct(PaymentTransactionProvider $paymentTransactionProvider)
    {
        $this->paymentTransactionProvider = $paymentTransactionProvider;
    }

    /**
     * @param Order $order
     *
     * @return bool|string
     */
    public function getPaymentMethod(Order $order)
    {
        $paymentTransaction = $this->paymentTransactionProvider->getPaymentTransaction($order);

        if (!$paymentTransaction) {
            return false;
        }

        return $paymentTransaction->getPaymentMethod();
    }
}
