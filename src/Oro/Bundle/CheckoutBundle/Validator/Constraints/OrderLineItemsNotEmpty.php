<?php

namespace Oro\Bundle\CheckoutBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates order line items created from a checkout for 2 cases:
 * 1) if order line items (at least one) can be added to the checkout;
 * 2) if there are no order line items can be added to order, then checks if order line items (at least one)
 *    can be added to RFP
 */
class OrderLineItemsNotEmpty extends Constraint
{
    public const string EMPTY_CODE = 'order_line_items_empty';
    public const string EMPTY_FOR_RFP_CODE = 'order_line_items_empty_for_rfp';

    public string $notEmptyMessage = 'oro.checkout.validator.order_line_items_not_empty.allow_rfp.message';
    public string $notEmptyForRfpMessage = 'oro.checkout.validator.order_line_items_not_empty.not_allow_rfp.message';

    #[\Override]
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
