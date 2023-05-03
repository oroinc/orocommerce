<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
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
        $lineItems = $checkout->getLineItems();
        $shippingCost = 0.00;

        $lineItemsWithShipping = $lineItems->filter(
            fn (CheckoutLineItem $lineItem) => $lineItem->hasShippingMethodData()
        );
        foreach ($lineItemsWithShipping as $lineItem) {
            if ($lineItem->getShippingCost()) {
                $shippingCost += $lineItem->getShippingCost()->getValue();
                continue;
            }

            $shippingCost += $this->lineItemShippingPriceProvider->getPrice($lineItem)->getValue();
        }

        return $shippingCost;
    }
}
