<?php

namespace OroB2B\Bundle\PaymentBundle\Entity;

interface PaymentMethodAwareInterface
{
    /**
     * @return string
     */
    public function getPaymentMethod();

    /**
     * @param string $paymentMethod
     * @return $this
     */
    public function setPaymentMethod($paymentMethod);
}
