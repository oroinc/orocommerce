<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * Checkout workflow shipping method-related actions.
 */
interface ShippingMethodActionsInterface
{
    public function hasApplicableShippingRules(Checkout $checkout, ?Collection $errors): bool;

    /**
     * @template LineItemIdentifier of string Example 'productSku:unitCode'
     * @template LineItemGroupsShippingMethod of array{'method': string, 'type': string}
     *
     * @param Checkout $checkout
     * @param null|array<LineItemIdentifier, LineItemGroupsShippingMethod> $lineItemsShippingMethods
     *       Example ['2BV:item' => ['method' => 'flat_rate_1', 'type' => 'primary'], ... ]
     * @param null|array<LineItemIdentifier, LineItemGroupsShippingMethod> $lineItemGroupsShippingMethods
     *       Example ['2BV:item' => ['method' => 'flat_rate_1', 'type' => 'primary'], ... ]
     * @param bool $useDefaults
     *
     * @return void
     */
    public function updateDefaultShippingMethods(
        Checkout $checkout,
        ?array $lineItemsShippingMethods,
        ?array $lineItemGroupsShippingMethods,
        bool $useDefaults = true
    ): void;

    /**
     * @template LineItemIdentifier of string Example 'productSku:unitCode'
     * @template LineItemGroupsShippingMethod of array{'method': string, 'type': string}
     *
     * @param Checkout $checkout
     * @param null|array<LineItemIdentifier, LineItemGroupsShippingMethod> $lineItemsShippingMethods
     *       Example ['2BV:item' => ['method' => 'flat_rate_1', 'type' => 'primary'], ... ]
     * @param null|array<LineItemIdentifier, LineItemGroupsShippingMethod> $lineItemGroupsShippingMethods
     *       Example ['2BV:item' => ['method' => 'flat_rate_1', 'type' => 'primary'], ... ]
     *
     * @return void
     */
    public function actualizeShippingMethods(
        Checkout $checkout,
        ?array $lineItemsShippingMethods,
        ?array $lineItemGroupsShippingMethods
    ): void;

    public function updateCheckoutShippingPrices(Checkout $checkout): void;
}
