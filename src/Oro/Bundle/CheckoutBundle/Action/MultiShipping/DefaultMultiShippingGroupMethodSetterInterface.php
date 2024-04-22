<?php

namespace Oro\Bundle\CheckoutBundle\Action\MultiShipping;


use Oro\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * Sets a default shipping method and a shipping cost for a checkout and its line item groups
 * when Multi Shipping Per Line Item Groups functionality is enabled.
 */
interface DefaultMultiShippingGroupMethodSetterInterface
{
    public function setDefaultShippingMethods(
        Checkout $checkout,
        ?array $lineItemGroupsShippingMethods = null,
        bool $useDefaults = false
    ): void;
}
