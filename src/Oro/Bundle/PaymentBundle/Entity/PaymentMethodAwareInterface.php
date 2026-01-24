<?php

namespace Oro\Bundle\PaymentBundle\Entity;

/**
 * Defines the contract for entities that are aware of and can store a payment method identifier.
 */
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
