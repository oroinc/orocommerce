<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItem\LineItemShippingPriceProviderInterface;

/**
 * Calculates total shipping cost value for Multi Shipping integration.
 */
class MultiShippingCostProvider
{
    private LineItemShippingPriceProviderInterface $lineItemShippingPriceProvider;

    public function __construct(LineItemShippingPriceProviderInterface $lineItemShippingPriceProvider)
    {
        $this->lineItemShippingPriceProvider = $lineItemShippingPriceProvider;
    }

    public function getCalculatedMultiShippingCost(Checkout $checkout): float
    {
        $shippingCost = 0.0;
        $lineItems = $checkout->getLineItems();
        foreach ($lineItems as $lineItem) {
            if ($lineItem->hasShippingMethodData() && $lineItem->getShippingCost()) {
                $shippingCost += $lineItem->getShippingCost()->getValue();
            } else {
                $shippingCost += $this->lineItemShippingPriceProvider->getPrice($lineItem)?->getValue();
            }
        }

        return $shippingCost;
    }
}
