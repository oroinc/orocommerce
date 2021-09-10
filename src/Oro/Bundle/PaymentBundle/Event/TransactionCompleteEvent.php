<?php

namespace Oro\Bundle\PaymentBundle\Event;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Symfony\Contracts\EventDispatcher\Event;

class TransactionCompleteEvent extends Event
{
    const NAME = 'oro_payment.event.transaction_complete';

    /** @var PaymentTransaction */
    protected $transaction;

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
