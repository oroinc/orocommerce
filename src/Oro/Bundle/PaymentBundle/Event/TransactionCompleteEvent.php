<?php

namespace Oro\Bundle\PaymentBundle\Event;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Payment Transaction Complete Event
 */
class TransactionCompleteEvent extends Event
{
    public const NAME = 'oro_payment.event.transaction_complete';

    public function __construct(
        private PaymentTransaction $transaction
    ) {
    }

    /**
     * @return PaymentTransaction
     */
    public function getTransaction(): PaymentTransaction
    {
        return $this->transaction;
    }
}
