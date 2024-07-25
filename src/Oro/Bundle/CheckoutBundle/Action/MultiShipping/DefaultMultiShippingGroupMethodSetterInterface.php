<?php

namespace Oro\Bundle\CheckoutBundle\Action\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * Sets a default shipping method and a shipping cost for a checkout and its line item groups
 * when Multi Shipping Per Line Item Groups functionality is enabled.
 */
interface DefaultMultiShippingGroupMethodSetterInterface
{
    /**
     * @template LineItemIdentifier of string Example 'productSku:unitCode'
     * @template LineItemGroupsShippingMethod of array{'method': string, 'type': string}
     *
     * @param Checkout $checkout
     * @param null|array<LineItemIdentifier, LineItemGroupsShippingMethod> $lineItemGroupsShippingMethods
     *      Example ['2BV:item' => ['method' => 'flat_rate_1', 'type' => 'primary'], ... ]
     * @param bool $useDefaults
     * @return void
     */
    public function setDefaultShippingMethods(
        Checkout $checkout,
        ?array $lineItemGroupsShippingMethods = null,
        bool $useDefaults = false
    ): void;
}
