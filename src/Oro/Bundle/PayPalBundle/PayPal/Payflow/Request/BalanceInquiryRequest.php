<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Request;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Transaction;

/**
 * Represents a balance inquiry request for PayPal Payflow transactions.
 *
 * Handles balance inquiry transaction type (not yet implemented).
 */
class BalanceInquiryRequest extends AbstractRequest
{
    #[\Override]
    public function getTransactionType()
    {
        throw new \BadMethodCallException(
            sprintf('Request type "%s" is not implemented yet', get_class($this))
        );

        return Transaction::BALANCE_INQUIRY;
    }
}
