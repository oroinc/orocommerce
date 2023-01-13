<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItem;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;

/**
 * Basic interface for classes which provides line item shipping price.
 */
interface LineItemShippingPriceProviderInterface
{
    public function getPrice(CheckoutLineItem $lineItem): ?Price;
}
