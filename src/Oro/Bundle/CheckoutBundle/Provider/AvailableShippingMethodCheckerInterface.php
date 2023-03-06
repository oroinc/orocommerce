<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * Represents a service to check whether at least one shipping method is available for a specific checkout.
 */
interface AvailableShippingMethodCheckerInterface
{
    public function hasAvailableShippingMethods(Checkout $checkout): bool;
}
