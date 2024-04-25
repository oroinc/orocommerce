<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;

interface ShippingMethodActionsInterface
{
    public function hasApplicableShippingRules(Checkout $checkout, ?Collection $errors): bool;

    public function updateDefaultShippingMethods(
        Checkout $checkout,
        ?array $lineItemsShippingMethods,
        ?array $lineItemGroupsShippingMethods
    ): void;

    public function actualizeShippingMethods(
        Checkout $checkout,
        ?array $lineItemsShippingMethods,
        ?array $lineItemGroupsShippingMethods
    ): void;

    public function updateCheckoutShippingPrices(Checkout $checkout): void;
}
