<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItem;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;

/**
 * Represents a service to calculate a shipping price for a checkout line item.
 */
interface LineItemShippingPriceProviderInterface
{
    public function getPrice(CheckoutLineItem $lineItem): ?Price;
}
