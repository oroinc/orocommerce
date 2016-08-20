<?php

namespace Oro\Bundle\PaymentBundle\Event;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

use Symfony\Component\EventDispatcher\Event;

class TransactionCompleteEvent extends Event
{
    const NAME = 'orob2b_payment.event.transaction_complete';

    /** @var PaymentTransaction */
    protected $transaction;

    /**
     * @param PaymentTransaction $transaction
     */
    public function __construct(PaymentTransaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * @return PaymentTransaction
     */
    public function getTransaction()
    {
        return $this->transaction;
    }
}
