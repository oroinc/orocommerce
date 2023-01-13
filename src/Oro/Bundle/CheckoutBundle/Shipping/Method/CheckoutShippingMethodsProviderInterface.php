<?php

namespace Oro\Bundle\CheckoutBundle\Shipping\Method;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;

/**
 * Represents a service to provide views for all applicable shipping methods and calculate a shipping price
 * for a specific checkout.
 */
interface CheckoutShippingMethodsProviderInterface
{
    public function getApplicableMethodsViews(Checkout $checkout): ShippingMethodViewCollection;

    public function getPrice(Checkout $checkout): ?Price;
}
