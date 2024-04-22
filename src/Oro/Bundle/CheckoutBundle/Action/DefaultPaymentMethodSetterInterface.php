<?php

namespace Oro\Bundle\CheckoutBundle\Action;


use Oro\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * Sets the first applicable payment method for checkout.
 */
interface DefaultPaymentMethodSetterInterface
{
    public function setDefaultPaymentMethod(Checkout $checkout): void;
}
