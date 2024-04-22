<?php

namespace Oro\Bundle\CheckoutBundle\Action;


use Oro\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * Sets a default shipping method and a shipping cost for a checkout.
 */
interface DefaultShippingMethodSetterInterface
{
    public function setDefaultShippingMethod(Checkout $checkout): void;
}
