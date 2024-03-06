<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemsGrouping;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;

/**
 * Represents a service that provide available shipping methods for a specific group of line items.
 */
interface LineItemGroupShippingMethodsProviderInterface
{
    /**
     * @param CheckoutLineItem[] $lineItems
     * @param string             $lineItemGroupKey
     *
     * @return array
     */
    public function getAvailableShippingMethods(array $lineItems, string $lineItemGroupKey): array;
}
