<?php

namespace Oro\Bundle\PaymentBundle\Event;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Provides common functionality for payment transaction-related events.
 *
 * This base class encapsulates a {@see PaymentTransaction} entity and provides access to it
 * for event listeners. Subclasses should extend this to create specific transaction events
 * that are dispatched at different stages of the payment processing lifecycle.
 */
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

    public function setPaymentTransaction(PaymentTransaction $paymentTransaction)
    {
        $this->paymentTransaction = $paymentTransaction;
    }
}
