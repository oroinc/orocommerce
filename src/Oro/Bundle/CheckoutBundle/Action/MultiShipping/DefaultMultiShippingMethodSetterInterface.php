<?php

namespace Oro\Bundle\CheckoutBundle\Action\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * Sets a default shipping method and a shipping cost for a checkout and its line items
 * when Multi Shipping Per Line Items functionality is enabled.
 */
interface DefaultMultiShippingMethodSetterInterface
{
    /**
     * @template LineItemIdentifier of string Example 'productSku:unitCode'
     * @template LineItemGroupsShippingMethod of array{'method': string, 'type': string}
     *
     * @param Checkout $checkout
     * @param null|array<LineItemIdentifier, LineItemGroupsShippingMethod> $lineItemsShippingMethods
     *      Example ['2BV:item' => ['method' => 'flat_rate_1', 'type' => 'primary'], ... ]
     * @param bool $useDefaults
     * @return void
     */
    public function setDefaultShippingMethods(
        Checkout $checkout,
        ?array $lineItemsShippingMethods = null,
        bool $useDefaults = false
    ): void;
}
