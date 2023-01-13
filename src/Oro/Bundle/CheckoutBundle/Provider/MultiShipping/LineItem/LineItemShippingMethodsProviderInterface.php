<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItem;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;

/**
 * Represents a service that provide available shipping methods for a specific line item.
 */
interface LineItemShippingMethodsProviderInterface
{
    public function getAvailableShippingMethods(CheckoutLineItem $lineItem): array;
}
