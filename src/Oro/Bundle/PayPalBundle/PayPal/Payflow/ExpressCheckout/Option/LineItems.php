<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\LineItems as BaseLineItems;

/**
 * Configures line items option for PayPal Express Checkout transactions.
 *
 * Extends base line items option with Express Checkout-specific applicability,
 * allowing line items for SET_EC and DO_EC actions.
 */
class LineItems extends BaseLineItems
{
    #[\Override]
    public function isApplicableDependent(array $options)
    {
        if (!isset($options[Action::ACTION])) {
            return false;
        }

        return in_array($options[Action::ACTION], [Action::SET_EC, Action::DO_EC], true);
    }
}
