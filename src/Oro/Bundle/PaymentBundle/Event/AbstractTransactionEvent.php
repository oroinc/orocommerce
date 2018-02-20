<?php

namespace Oro\Bundle\PaymentBundle\Event;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Symfony\Component\EventDispatcher\Event;

abstract class AbstractTransactionEvent extends Event
{
    /** @var PaymentTransaction|null */
    protected $paymentTransaction;

    /**
     * @return PaymentTransaction|null
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
