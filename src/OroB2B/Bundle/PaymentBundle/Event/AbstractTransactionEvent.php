<?php

namespace OroB2B\Bundle\PaymentBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;

class AbstractTransactionEvent extends Event
{
    /** @var PaymentTransaction */
    protected $paymentTransaction;

    /**
     * @return PaymentTransaction
     */
    public function getPaymentTransaction()
    {
        return $this->paymentTransaction;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     */
    public function setPaymentTransaction(PaymentTransaction $paymentTransaction)
    {
        $this->paymentTransaction = $paymentTransaction;
    }
}
