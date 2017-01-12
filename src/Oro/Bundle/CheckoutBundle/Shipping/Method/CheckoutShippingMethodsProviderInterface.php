<?php

namespace Oro\Bundle\CheckoutBundle\Shipping\Method;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;

interface CheckoutShippingMethodsProviderInterface
{
    /**
     * @param Checkout $checkout
     *
     * @return ShippingMethodViewCollection
     */
    public function getApplicableMethodsViews(Checkout $checkout);

    /**
     * @param Checkout $checkout
     *
     * @return Price|null
     */
    public function getPrice(Checkout $checkout);
}
