<?php

namespace OroB2B\Bundle\PaymentBundle\Entity;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

interface PaymentMethodAwareInterface
{
    /**
     * @return string
     */
    public function getPaymentMethod();

    /**
     * @param string $paymentMethod
     * @return Checkout
     */
    public function setPaymentMethod($paymentMethod);
}
