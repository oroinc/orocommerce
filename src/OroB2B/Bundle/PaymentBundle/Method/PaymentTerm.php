<?php

namespace OroB2B\Bundle\PaymentBundle\Method;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;

class PaymentTerm implements PaymentMethodInterface
{
    const TYPE = 'PaymentTerm';

    /** {@inheritdoc} */
    public function execute(PaymentTransaction $paymentTransaction)
    {
        $paymentTransaction->setSuccessful(true);

        return [];
    }

    /** {@inheritdoc} */
    public function getType()
    {
        return self::TYPE;
    }
}
