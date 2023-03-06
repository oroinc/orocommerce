<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;

/**
 * Represents a service to get a shipping origin for a specific checkout.
 */
interface CheckoutShippingOriginProviderInterface
{
    public function getShippingOrigin(Checkout $checkout): ShippingOrigin;
}
