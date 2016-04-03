<?php

namespace OroB2B\Bundle\PaymentBundle\Method;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;

interface PaymentMethodInterface
{
    const AUTHORIZE = 'authorize';
    const CAPTURE = 'capture';
    const CHARGE = 'charge';
    const VOID = 'void';

    /**
     * @param PaymentTransaction $paymentTransaction
     */
    public function execute(PaymentTransaction $paymentTransaction);

    /**
     * @return string
     */
    public function getType();
}
