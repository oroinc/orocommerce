<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * Expects checkout as input. Checks order line items created from checkout for 2 cases:
 * 1) if order line items (at least one) can be added to the checkout and sets $.orderLineItemsNotEmpty variable;
 * 2) if there are no order line items can be added to order, then checks if order line items (at least one)
 *    can be added to RFP and sets $.orderLineItemsNotEmptyForRfp variable.
 */
interface OrderLineItemsNotEmptyInterface
{
    /**
     * @param Checkout $checkout
     * @return array{
     *     orderLineItems: array,
     *     orderLineItemsNotEmpty: array,
     *     orderLineItemsForRfp: array,
     *     orderLineItemsNotEmptyForRfp: array
     * }
     */
    public function execute(Checkout $checkout): array;
}
