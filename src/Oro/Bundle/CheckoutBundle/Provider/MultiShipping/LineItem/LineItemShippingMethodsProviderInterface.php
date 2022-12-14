<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItem;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;

/**
 * Basic interface for classes which provides available shipping methods for line item.
 */
interface LineItemShippingMethodsProviderInterface
{
    public function getAvailableShippingMethods(CheckoutLineItem $lineItem): array;
}
