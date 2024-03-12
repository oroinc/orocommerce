<?php

namespace Oro\Bundle\CheckoutBundle\Shipping\Method;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * Represents a service that provide views for all applicable shipping methods and calculate a shipping price
 * for a specific group of checkout line items.
 */
interface CheckoutGroupShippingMethodsProviderInterface
{
    public function getGroupedApplicableMethodsViews(Checkout $checkout, array $groupedLineItemIds): array;

    public function getCurrentShippingMethods(Checkout $checkout): array;
}
