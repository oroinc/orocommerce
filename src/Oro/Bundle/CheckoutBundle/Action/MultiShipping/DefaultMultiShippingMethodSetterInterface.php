<?php

namespace Oro\Bundle\CheckoutBundle\Action\MultiShipping;


use Oro\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * Sets a default shipping method and a shipping cost for a checkout and its line items
 * when Multi Shipping Per Line Items functionality is enabled.
 */
interface DefaultMultiShippingMethodSetterInterface
{
    public function setDefaultShippingMethods(
        Checkout $checkout,
        ?array $lineItemsShippingMethods = null,
        bool $useDefaults = false
    ): void;
}
