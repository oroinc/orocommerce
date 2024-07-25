<?php

namespace Oro\Bundle\CheckoutBundle\Manager\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * Implements logic to handle line item groups shipping data actions.
 */
interface CheckoutLineItemGroupsShippingManagerInterface
{
    /**
     * Updates shipping methods for line item groups from provided data.
     *
     * @param array|null $shippingData ['product.category:1' => ['method' => 'flat_rate_1', 'type' => 'primary'], ... ]
     * @param Checkout $checkout
     * @param bool $useDefaults
     */
    public function updateLineItemGroupsShippingMethods(
        ?array $shippingData,
        Checkout $checkout,
        bool $useDefaults = false
    ): void;

    /**
     * Gets line item groups shipping data.
     *
     * @param Checkout $checkout
     *
     * @return array ['product.category:1' => ['method' => 'flat_rate_1', 'type' => 'primary'], ... ]
     */
    public function getCheckoutLineItemGroupsShippingData(Checkout $checkout): array;

    public function updateLineItemGroupsShippingPrices(Checkout $checkout): void;
}
