<?php

namespace Oro\Bundle\CheckoutBundle\Manager\MultiShipping;


use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Implements logic to handle line items shipping data actions.
 */
interface CheckoutLineItemsShippingManagerInterface
{
    /**
     * Update line items shipping methods from provided data.
     *
     * @param array|null $shippingData ['2BV:item' => ['method' => 'flat_rate_1', 'type' => 'primary'], ... ]
     * @param Checkout $checkout
     * @param bool $useDefaults
     */
    public function updateLineItemsShippingMethods(
        ?array $shippingData,
        Checkout $checkout,
        bool $useDefaults = false
    ): void;

    /**
     * Build lineItems shipping data.
     *
     * @param Checkout $checkout
     *
     * @return array ['2BV:item' => ['method' => 'flat_rate_1', 'type' => 'primary'], ... ]
     */
    public function getCheckoutLineItemsShippingData(Checkout $checkout): array;

    public function updateLineItemsShippingPrices(Checkout $checkout): void;

    public function getLineItemIdentifier(ProductLineItemInterface $lineItem): string;
}
