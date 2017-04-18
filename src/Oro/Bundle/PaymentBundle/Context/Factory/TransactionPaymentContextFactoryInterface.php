<?php

namespace Oro\Bundle\PaymentBundle\Context\Factory;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

interface TransactionPaymentContextFactoryInterface
{
    /**
     * @param PaymentTransaction $transaction
     *
     * @return PaymentContextInterface|null
     */
    public function create(PaymentTransaction $transaction);
}
