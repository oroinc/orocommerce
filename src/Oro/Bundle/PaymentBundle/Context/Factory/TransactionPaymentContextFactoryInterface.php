<?php

namespace Oro\Bundle\PaymentBundle\Context\Factory;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

/**
 * Defines the contract for creating payment context from a payment transaction.
 */
interface TransactionPaymentContextFactoryInterface
{
    /**
     * @param PaymentTransaction $transaction
     *
     * @return PaymentContextInterface|null
     */
    public function create(PaymentTransaction $transaction);
}
